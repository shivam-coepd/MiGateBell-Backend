<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../helpers/app_userID_helper.php';

/**
 * SuperAdminController
 *
 * Design contract:
 *  - society_registrations  = permanent lead/audit table. NEVER deleted. Status: new → under_review → approved | rejected
 *  - societies              = live society table. Rows are created only when a super admin approves a lead (or adds a society manually) — never from the public landing form.
 *  - Linked via societies.registration_id → society_registrations.id
 *  - Any status/field change on either table is immediately mirrored to the other via syncSocietyToReg() / syncRegToSociety()
 */
class SuperAdminController extends BaseController
{
    // ─── PRIVATE HELPERS ────────────────────────────────────────────────────

    /**
     * Ensure all extra columns exist in societies table.
     * Called once per request that writes to societies. Safe to run repeatedly.
     */
    private function ensureSocietyColumns(): void
    {
        $alters = [
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `code` varchar(20) DEFAULT NULL AFTER `name`",
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `towers` int(11) DEFAULT 1 AFTER `pincode`",
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `total_flats` int(11) DEFAULT 0 AFTER `towers`",
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `admin_id` int(11) DEFAULT NULL AFTER `total_flats`",
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `gst` varchar(20) DEFAULT NULL AFTER `admin_id`",
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `pan` varchar(20) DEFAULT NULL AFTER `gst`",
            "ALTER TABLE `societies` ADD COLUMN IF NOT EXISTS `registration_id` int(11) DEFAULT NULL AFTER `pan`",
        ];
        foreach ($alters as $sql) {
            try { $this->db->exec($sql); } catch (Exception $e) {}
        }
        // Add unique constraint — silently ignore if already exists (duplicate key error 1061)
        try {
            $this->db->exec("ALTER TABLE `societies` ADD UNIQUE KEY `uq_societies_registration_id` (`registration_id`)");
        } catch (Exception $e) {}
    }

    /**
     * Mirror a society's key fields + status back to its linked registration row.
     * Called after any societies UPDATE.
     */
    private function syncSocietyToReg(int $societyId): void
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT s.registration_id, s.name, s.address, s.city, s.state, s.pincode,
                        s.contact_person, s.contact_phone, s.contact_email,
                        s.towers, s.total_flats, s.gst, s.pan, s.status
                 FROM societies s WHERE s.id = ?"
            );
            $stmt->execute([$societyId]);
            $soc = $stmt->fetch();
            if (!$soc || empty($soc['registration_id'])) return;

            // Map society status → registration status
            $statusMap = [
                'approved'  => 'approved',
                'pending'   => 'under_review',
                'verified'  => 'under_review',
                'rejected'  => 'rejected',
                'suspended' => 'rejected',
            ];
            $regStatus = $statusMap[$soc['status']] ?? 'under_review';

            $this->update('society_registrations', [
                'society_name'  => $soc['name'],
                'address'       => $soc['address'],
                'city'          => $soc['city'],
                'state'         => $soc['state'],
                'pincode'       => $soc['pincode'],
                'contact_name'  => $soc['contact_person'],
                'contact_phone' => $soc['contact_phone'],
                'contact_email' => $soc['contact_email'],
                'towers'        => $soc['towers'],
                'total_flats'   => $soc['total_flats'],
                'gst'           => $soc['gst'],
                'pan'           => $soc['pan'],
                'status'        => $regStatus,
                'reviewed_at'   => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $soc['registration_id']]);
        } catch (Exception $e) {
            error_log("syncSocietyToReg failed: " . $e->getMessage());
        }
    }

    /**
     * Mirror a registration's key fields + status to its linked society row (if exists).
     * Called after any society_registrations UPDATE.
     */
    private function syncRegToSociety(int $regId): void
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.society_name, r.address, r.city, r.state, r.pincode,
                        r.contact_name, r.contact_phone, r.contact_email,
                        r.towers, r.total_flats, r.gst, r.pan, r.status
                 FROM society_registrations r WHERE r.id = ?"
            );
            $stmt->execute([$regId]);
            $reg = $stmt->fetch();
            if (!$reg) return;

            // Check if a society is linked to this registration
            $stmt2 = $this->db->prepare("SELECT id FROM societies WHERE registration_id = ?");
            $stmt2->execute([$regId]);
            $soc = $stmt2->fetch();
            if (!$soc) return;

            $statusMap = [
                'approved'     => 'approved',
                'under_review' => 'pending',
                'rejected'     => 'rejected',
                'pending'      => 'pending',
                'new'          => 'pending',
            ];
            $socStatus = $statusMap[$reg['status']] ?? 'pending';

            $this->update('societies', [
                'name'           => $reg['society_name'],
                'address'        => $reg['address'],
                'city'           => $reg['city'],
                'state'          => $reg['state'],
                'pincode'        => $reg['pincode'],
                'contact_person' => $reg['contact_name'],
                'contact_phone'  => $reg['contact_phone'],
                'contact_email'  => $reg['contact_email'],
                'towers'         => $reg['towers'],
                'total_flats'    => $reg['total_flats'],
                'gst'            => $reg['gst'],
                'pan'            => $reg['pan'],
                'status'         => $socStatus,
            ], 'id = :id', ['id' => $soc['id']]);
        } catch (Exception $e) {
            error_log("syncRegToSociety failed: " . $e->getMessage());
        }
    }

    /** Normalize phone number - strip non-digits and remove 91 prefix (consistent with AuthController) */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
            $digits = substr($digits, 2);
        }
        return $digits;
    }

    /**
     * Resolve the single society row to activate for a registration lead (avoids duplicate INSERTs).
     * Order: already linked by registration_id → unlinked / same-reg row matching email or phone or name.
     */
    private function findSocietyIdForRegistrationLead(int $regId, array $lead, string $normalizedPhone): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM societies WHERE registration_id = ? ORDER BY id ASC LIMIT 1');
        $stmt->execute([$regId]);
        $row = $stmt->fetch();
        if ($row) {
            return (int) $row['id'];
        }

        $email = trim((string) ($lead['contact_email'] ?? ''));
        $name  = trim((string) ($lead['society_name'] ?? ''));
        $rawPhone = trim((string) ($lead['contact_phone'] ?? ''));

        $conds  = ['(registration_id IS NULL OR registration_id = ?)'];
        $params = [$regId];

        $matchParts = [];
        if ($email !== '') {
            $matchParts[] = 'NULLIF(TRIM(contact_email), \'\') IS NOT NULL AND contact_email = ?';
            $params[] = $email;
        }
        if ($normalizedPhone !== '') {
            $matchParts[] = 'contact_phone = ?';
            $params[] = $normalizedPhone;
        }
        if ($rawPhone !== '' && $rawPhone !== $normalizedPhone) {
            $matchParts[] = 'contact_phone = ?';
            $params[] = $rawPhone;
        }
        if ($name !== '') {
            $matchParts[] = 'name = ?';
            $params[] = $name;
        }
        if (empty($matchParts)) {
            return null;
        }

        $conds[] = '(' . implode(' OR ', $matchParts) . ')';
        $sql = 'SELECT id FROM societies WHERE ' . implode(' AND ', $conds) . ' ORDER BY id ASC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? (int) $row['id'] : null;
    }

    /** Find society id conflicting on name / email / phone (phones compared normalized + raw). */
    private function findDuplicateSocietyId(string $name, ?string $email, ?string $normalizedPhone, ?string $rawPhone): ?int
    {
        $conds  = ['name = ?'];
        $params = [$name];
        if ($email !== null && $email !== '') {
            $conds[] = '(NULLIF(TRIM(contact_email), \'\') IS NOT NULL AND contact_email = ?)';
            $params[] = $email;
        }
        if ($normalizedPhone !== null && $normalizedPhone !== '') {
            $conds[] = 'contact_phone = ?';
            $params[] = $normalizedPhone;
        }
        if ($rawPhone !== null && $rawPhone !== '' && $rawPhone !== $normalizedPhone) {
            $conds[] = 'contact_phone = ?';
            $params[] = $rawPhone;
        }
        $sql = 'SELECT id FROM societies WHERE ' . implode(' OR ', $conds) . ' ORDER BY id ASC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? (int) $row['id'] : null;
    }

    /**
     * Equality on string/ENUM columns that may use utf8mb4_general_ci while bound values use utf8mb4_unicode_ci (or vice versa).
     * Coerces both sides to utf8mb4_unicode_ci to avoid MySQL error 1267.
     */
    private function sqlUnicodeEq(string $leftSql, string $placeholder = '?'): string
    {
        return "CONVERT(($leftSql) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT($placeholder USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    }

    // ─── STATS ──────────────────────────────────────────────────────────────
    public function getStats()
    {
        try {
            $this->auth->authorize('super_admin');
            $stats = [];

            $stmt = $this->db->query("SELECT COUNT(*) as count FROM societies");
            $stats['totalSocieties'] = $stmt->fetch()['count'];

            try {
                foreach (['approved','pending','verified'] as $s) {
                    $wc = $this->sqlUnicodeEq('`status`');
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM societies WHERE $wc");
                    $stmt->execute([$s]);
                    $stats[$s] = $stmt->fetch()['count'];
                }
            } catch (Exception $e) {
                $stats['approved'] = $stats['pending'] = $stats['verified'] = 0;
            }

            try {
                $wcNew = $this->sqlUnicodeEq('`status`');
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM society_registrations WHERE $wcNew");
                $stmt->execute(['new']);
                $stats['newLeads'] = $stmt->fetch()['count'];
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM society_registrations WHERE $wcNew");
                $stmt->execute(['under_review']);
                $stats['underReview'] = $stmt->fetch()['count'];
            } catch (Exception $e) {
                $stats['newLeads'] = $stats['underReview'] = 0;
            }

            $wcAdmin = $this->sqlUnicodeEq('`role`');
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE $wcAdmin");
            $stmt->execute(['admin']);
            $stats['totalAdmins'] = $stmt->fetch()['count'];
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE $wcAdmin");
            $stmt->execute(['resident']);
            $stats['totalResidents'] = $stmt->fetch()['count'];

            $stats['trend'] = [];
            for ($i = 5; $i >= 0; $i--) {
                $d = new DateTime("-$i months");
                $wcMonth = $this->sqlUnicodeEq("DATE_FORMAT(`created_at`,'%Y-%m')");
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM societies WHERE $wcMonth");
                $stmt->execute([$d->format('Y-m')]);
                $stats['trend'][] = ['month' => $d->format('M y'), 'count' => $stmt->fetch()['count']];
            }

            $stats['planDist'] = [];
            foreach (['starter','professional','enterprise'] as $plan) {
                try {
                    $wcPlan = $this->sqlUnicodeEq('`plan`');
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM societies WHERE $wcPlan");
                    $stmt->execute([$plan]);
                    $stats['planDist'][] = ['plan' => $plan, 'count' => $stmt->fetch()['count']];
                } catch (Exception $e) {
                    $stats['planDist'][] = ['plan' => $plan, 'count' => 0];
                }
            }

            Response::success("Stats retrieved", $stats);
        } catch (Exception $e) {
            error_log("Get stats error: " . $e->getMessage());
            Response::error("Failed to retrieve stats: " . $e->getMessage(), 500);
        }
    }

    // ─── REGISTRATIONS ──────────────────────────────────────────────────────

    public function getRegistrations()
    {
        try {
            $this->auth->authorize('super_admin');
            $stmt = $this->db->query(
                "SELECT id, society_name as societyName, address, city, state, pincode,
                        towers, total_flats as totalFlats,
                        contact_name as contactName, contact_email as contactEmail, contact_phone as contactPhone,
                        gst, pan, message, status, created_at as createdAt
                 FROM society_registrations ORDER BY created_at DESC"
            );
            Response::success("Registrations retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve registrations: " . $e->getMessage(), 500);
        }
    }

    /**
     * Public landing-page lead capture. Writes ONLY `society_registrations` (never `societies`).
     * No auth. Use POST /api/public/society-registrations in new clients.
     */
    public function createPublicSocietyRegistration()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $errors = $this->validateRequiredFields($data, ['societyName','city','contactName','contactEmail','contactPhone']);
            if (!empty($errors)) Response::validationError($errors);

            $id = $this->insert('society_registrations', [
                'society_name'  => $data['societyName'],
                'address'       => $data['address']      ?? '',
                'city'          => $data['city'],
                'state'         => $data['state']        ?? '',
                'pincode'       => $data['pincode']      ?? '',
                'towers'        => $data['towers']       ?? 1,
                'total_flats'   => $data['totalFlats']   ?? 0,
                'contact_name'  => $data['contactName'],
                'contact_email' => $data['contactEmail'],
                'contact_phone' => $data['contactPhone'],
                'gst'           => $data['gst']          ?? null,
                'pan'           => $data['pan']          ?? null,
                'message'       => $data['message']      ?? null,
                'status'        => 'new',
            ]);
            Response::success("Registration submitted successfully. Our team will review and contact you shortly.", ['id' => $id], 201);
        } catch (Exception $e) {
            Response::error("Failed to create registration: " . $e->getMessage(), 500);
        }
    }

    /** @deprecated Legacy URL — use createPublicSocietyRegistration / POST /api/public/society-registrations */
    public function createRegistration()
    {
        $this->createPublicSocietyRegistration();
    }

    /**
     * Merge optional JSON body from POST /registrations/:id/approve into a fetched lead row (camelCase keys).
     * Does not handle adminPassword or plan (handled separately in approveRegistrationLead).
     */
    private function mergeApprovePayloadIntoLead(array $lead, array $payload): array
    {
        if (empty($payload)) {
            return $lead;
        }

        $applyString = function (string $snake, array $keys) use (&$lead, $payload): void {
            foreach ($keys as $k) {
                if (!array_key_exists($k, $payload)) {
                    continue;
                }
                $v = $payload[$k];
                if ($v === null) {
                    return;
                }
                if (is_string($v)) {
                    $v = trim($v);
                }
                if (in_array($snake, ['gst', 'pan'], true) && $v === '') {
                    $v = null;
                }
                $lead[$snake] = $v;

                return;
            }
        };

        $applyString('society_name', ['societyName', 'society_name']);
        $applyString('address', ['address']);
        $applyString('city', ['city']);
        $applyString('state', ['state']);
        $applyString('pincode', ['pincode']);
        $applyString('contact_name', ['contactName', 'contact_name']);
        $applyString('contact_email', ['contactEmail', 'contact_email']);
        $applyString('contact_phone', ['contactPhone', 'contact_phone']);
        $applyString('gst', ['gst']);
        $applyString('pan', ['pan']);
        $applyString('country', ['country']);

        if (array_key_exists('towers', $payload)) {
            $lead['towers'] = max(1, (int) $payload['towers']);
        }
        if (array_key_exists('totalFlats', $payload)) {
            $lead['total_flats'] = max(0, (int) $payload['totalFlats']);
        }
        if (array_key_exists('total_flats', $payload)) {
            $lead['total_flats'] = max(0, (int) $payload['total_flats']);
        }

        return $lead;
    }

    /** Super admin updates registration status (new → under_review | rejected). Syncs to societies. */
    public function updateRegistration($id)
    {
        try {
            $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);

            $allowed = [
                'status', 'reviewedBy' => 'reviewed_by', 'rejectionReason' => 'rejection_reason',
                'societyName' => 'society_name', 'address', 'city', 'state', 'pincode',
                'towers', 'totalFlats' => 'total_flats', 'contactName' => 'contact_name',
                'contactEmail' => 'contact_email', 'contactPhone' => 'contact_phone',
                'gst', 'pan', 'message'
            ];
            
            $updateData = [];
            foreach ($allowed as $key => $val) {
                $field = is_numeric($key) ? $val : $key;
                $dbCol = is_numeric($key) ? $val : $val;
                if (isset($data[$field])) $updateData[$dbCol] = $data[$field];
            }

            if (!empty($updateData)) {
                $updateData['reviewed_at'] = date('Y-m-d H:i:s');
                $this->update('society_registrations', $updateData, 'id = :id', ['id' => $id]);
                // Mirror status change to linked society (if it exists)
                $this->syncRegToSociety((int)$id);
            }

            Response::success("Registration updated");
        } catch (Exception $e) {
            Response::error("Failed to update registration: " . $e->getMessage(), 500);
        }
    }

    /**
     * Approve a registration lead:
     *  1. Create society row (idempotent — checks for existing society with same registration_id)
     *  2. Create or reuse admin user
     *  3. Mark registration as 'approved' (NOT deleted — kept for audit + sync)
     *
     * Optional JSON body (same session) lets super admin correct society + admin fields before activation:
     * societyName, address, city, state, pincode, country, towers, totalFlats, gst, pan,
     * contactName, contactEmail, contactPhone, plan (starter|professional|enterprise),
     * adminPassword (optional; min 8 chars if set — otherwise a random password is generated).
     */
    public function approveRegistrationLead($id)
    {
        try {
            $this->auth->authorize('super_admin');
            $this->ensureSocietyColumns();

            $payload = json_decode((string) file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = [];
            }

            // Fetch lead
            $stmt = $this->db->prepare("SELECT * FROM society_registrations WHERE id = ?");
            $stmt->execute([$id]);
            $lead = $stmt->fetch();
            if (!$lead) Response::notFound("Registration lead not found");

            $regId = (int) $id;
            if (($lead['status'] ?? '') === 'approved') {
                $stmt = $this->db->prepare('SELECT id, code FROM societies WHERE registration_id = ? ORDER BY id ASC LIMIT 1');
                $stmt->execute([$regId]);
                $linked = $stmt->fetch();
                if ($linked) {
                    Response::success('This registration is already approved.', [
                        'society_id'       => (int) $linked['id'],
                        'society_name'     => $lead['society_name'],
                        'code'             => $linked['code'] ?? '',
                        'admin_email'      => $lead['contact_email'],
                        'admin_phone'      => $this->normalizePhone((string) ($lead['contact_phone'] ?? '')),
                        'password'         => null,
                        'already_approved' => true,
                    ], 200);
                }
            }

            $lead = $this->mergeApprovePayloadIntoLead($lead, $payload);

            $req = ['society_name' => 'Society name', 'city' => 'City', 'contact_name' => 'Admin name', 'contact_email' => 'Admin email', 'contact_phone' => 'Admin phone'];
            $missing = [];
            foreach ($req as $field => $label) {
                if (!isset($lead[$field]) || trim((string) $lead[$field]) === '') {
                    $missing[] = "{$label} is required";
                }
            }
            if (!empty($missing)) {
                Response::validationError($missing);
            }
            if (!filter_var($lead['contact_email'], FILTER_VALIDATE_EMAIL)) {
                Response::error('Invalid admin email format', 422);
            }

            $plan = 'starter';
            if (!empty($payload['plan']) && in_array($payload['plan'], ['starter', 'professional', 'enterprise'], true)) {
                $plan = $payload['plan'];
            }

            $passwordPlain = 'Admin@' . rand(1000, 9999);
            if (!empty($payload['adminPassword'])) {
                if (strlen((string) $payload['adminPassword']) < 8) {
                    Response::error('adminPassword must be at least 8 characters when provided', 422);
                }
                $passwordPlain = (string) $payload['adminPassword'];
            }

            $normalizedPhone = $this->normalizePhone((string) ($lead['contact_phone'] ?? ''));
            $societyId = $this->findSocietyIdForRegistrationLead($regId, $lead, $normalizedPhone);
            $isNewSociety = !$societyId;

            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) ($lead['society_name'] ?? '')), 0, 4)) . rand(100, 999);
            $codeRow = [];
            if (!$isNewSociety) {
                $stmt = $this->db->prepare('SELECT code FROM societies WHERE id = ?');
                $stmt->execute([$societyId]);
                $codeRow = $stmt->fetch() ?: [];
                if (!empty($codeRow['code'])) {
                    $code = $codeRow['code'];
                }
            }

            $this->beginTransaction();

            if ($isNewSociety) {
                // Create society
                $societyId = $this->insert('societies', [
                    'name'            => $lead['society_name'],
                    'code'            => $code,
                    'address'         => $lead['address']     ?? '',
                    'city'            => $lead['city'],
                    'state'           => $lead['state']       ?? '',
                    'country'         => $lead['country'] ?? 'India',
                    'pincode'         => $lead['pincode']     ?? '',
                    'contact_person'  => $lead['contact_name'],
                    'contact_phone'   => $normalizedPhone,
                    'contact_email'   => $lead['contact_email'],
                    'plan'            => $plan,
                    'towers'          => $lead['towers']      ?? 1,
                    'total_flats'     => $lead['total_flats'] ?? 0,
                    'gst'             => $lead['gst']         ?: null,
                    'pan'             => $lead['pan']         ?: null,
                    'registration_id' => (int)$id,
                    'status'          => 'approved',
                ]);
            } else {
                // Update existing society and link it to this registration
                $upd = [
                    'registration_id' => (int) $id,
                    'status'          => 'approved',
                    'name'            => $lead['society_name'],
                    'address'         => $lead['address']     ?? '',
                    'city'            => $lead['city'],
                    'state'           => $lead['state']       ?? '',
                    'country'         => $lead['country'] ?? 'India',
                    'pincode'         => $lead['pincode']     ?? '',
                    'contact_person'  => $lead['contact_name'],
                    'contact_phone'   => $normalizedPhone,
                    'contact_email'   => $lead['contact_email'],
                    'plan'            => $plan,
                    'towers'          => $lead['towers']      ?? 1,
                    'total_flats'     => $lead['total_flats'] ?? 0,
                    'gst'             => $lead['gst']         ?: null,
                    'pan'             => $lead['pan']         ?: null,
                ];
                if (empty($codeRow['code'])) {
                    $upd['code'] = $code;
                }
                $this->update('societies', $upd, 'id = :id', ['id' => $societyId]);
            }

            // Create or reuse admin user
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$lead['contact_email'], $normalizedPhone]);
            $existingUser = $stmt->fetch();

            $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

            if ($existingUser) {
                $adminId = $existingUser['id'];
                $this->update('users', [
                    'name'       => $lead['contact_name'],
                    'email'      => $lead['contact_email'],
                    'phone'      => $normalizedPhone,
                    'role'       => 'admin',
                    'society_id' => $societyId,
                    'password'   => $passwordHash,
                    'status'     => 'active',
                ], 'id = :id', ['id' => $adminId]);
            } else {
                $appUserId = AppUserIdHelper::generateUnique($this->db);
                $adminId = $this->insert('users', [
                    'app_user_id' => $appUserId,
                    'name'        => $lead['contact_name'],
                    'email'       => $lead['contact_email'],
                    'phone'       => $normalizedPhone,
                    'password'    => $passwordHash,
                    'role'        => 'admin',
                    'society_id'  => $societyId,
                    'status'      => 'active',
                ]);
            }

            // Link admin to society
            $this->update('societies', ['admin_id' => $adminId], 'id = :id', ['id' => $societyId]);

            // Persist final lead snapshot + approved status (audit trail matches activated society)
            $this->update('society_registrations', [
                'society_name'  => $lead['society_name'],
                'address'       => $lead['address'] ?? '',
                'city'          => $lead['city'],
                'state'         => $lead['state'] ?? '',
                'pincode'       => $lead['pincode'] ?? '',
                'towers'        => $lead['towers'] ?? 1,
                'total_flats'   => $lead['total_flats'] ?? 0,
                'contact_name'  => $lead['contact_name'],
                'contact_email' => $lead['contact_email'],
                'contact_phone' => $lead['contact_phone'],
                'gst'           => $lead['gst'] ?: null,
                'pan'           => $lead['pan'] ?: null,
                'status'        => 'approved',
                'reviewed_at'   => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $id]);

            $this->commit();

            Response::success("Lead approved and society activated", [
                'society_id'   => $societyId,
                'society_name' => $lead['society_name'],
                'code'         => $code,
                'admin_email'  => $lead['contact_email'],
                'admin_phone'  => $normalizedPhone,
                'password'     => $passwordPlain,
            ]);
        } catch (Exception $e) {
            $this->rollback();
            error_log("Approve lead error: " . $e->getMessage());
            Response::error("Failed to approve lead: " . $e->getMessage(), 500);
        }
    }

    // ─── SOCIETIES ──────────────────────────────────────────────────────────

    public function getSocieties()
    {
        try {
            $this->auth->authorize('super_admin');
            $stmt = $this->db->query(
                "SELECT s.id, s.name, s.address, s.city, s.state, s.country, s.pincode,
                        s.contact_person as contactName, s.contact_phone as contactPhone, s.contact_email as contactEmail,
                        s.plan, s.created_at as createdAt,
                        COALESCE(s.status,'approved') as status,
                        COALESCE(s.code,'') as code,
                        COALESCE(s.total_flats,0) as totalFlats,
                        COALESCE(s.towers,1) as towers,
                        s.admin_id as adminId,
                        s.registration_id as registrationId
                 FROM societies s ORDER BY s.created_at DESC"
            );
            Response::success("Societies retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve societies: " . $e->getMessage(), 500);
        }
    }

    public function getSocietyById($id)
    {
        try {
            $this->auth->authorize('super_admin');
            $stmt = $this->db->prepare(
                "SELECT s.id, s.name, s.address, s.city, s.state, s.country, s.pincode,
                        s.contact_person as contactName, s.contact_phone as contactPhone, s.contact_email as contactEmail,
                        s.plan, s.created_at as createdAt,
                        COALESCE(s.status,'approved') as status,
                        COALESCE(s.code,'') as code,
                        COALESCE(s.total_flats,0) as totalFlats,
                        COALESCE(s.towers,1) as towers,
                        s.admin_id as adminId,
                        s.registration_id as registrationId,
                        s.gst, s.pan
                 FROM societies s WHERE s.id = ?"
            );
            $stmt->execute([$id]);
            $soc = $stmt->fetch();
            if (!$soc) Response::notFound("Society not found");

            // User count
            $stmt2 = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE society_id = ?");
            $stmt2->execute([$id]);
            $soc['userCount'] = $stmt2->fetch()['count'];

            // Admin info
            $soc['admin'] = null;
            $adminId = $soc['adminId'];
            if ($adminId) {
                $stmt3 = $this->db->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
                $stmt3->execute([$adminId]);
                $soc['admin'] = $stmt3->fetch() ?: null;
            }
            if (!$soc['admin']) {
                $stmt3 = $this->db->prepare("SELECT id, name, email, phone, role FROM users WHERE society_id = ? AND role = 'admin' LIMIT 1");
                $stmt3->execute([$id]);
                $soc['admin'] = $stmt3->fetch() ?: null;
                if ($soc['admin']) $soc['adminId'] = $soc['admin']['id'];
            }

            Response::success("Society retrieved", $soc);
        } catch (Exception $e) {
            Response::error("Failed to retrieve society: " . $e->getMessage(), 500);
        }
    }

    /** Create a society directly (super admin, not from a registration lead). */
    public function createSociety()
    {
        try {
            $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name','address','city']);
            if (!empty($errors)) Response::validationError($errors);

            $allowedPlans = ['starter','professional','enterprise'];
            $plan = isset($data['plan']) && in_array($data['plan'], $allowedPlans) ? $data['plan'] : 'starter';

            $phoneInput = $data['contact_phone'] ?? $data['contactPhone'] ?? null;
            $rawPhone = is_string($phoneInput) ? trim($phoneInput) : '';
            $normalizedPhone = $rawPhone !== '' ? $this->normalizePhone($rawPhone) : null;
            $emailInput = $data['contact_email'] ?? $data['contactEmail'] ?? null;
            $emailTrim = is_string($emailInput) ? trim($emailInput) : '';

            $regIdRaw = $data['registration_id'] ?? $data['registrationId'] ?? null;
            $regId = ($regIdRaw !== null && $regIdRaw !== '' && (int) $regIdRaw > 0) ? (int) $regIdRaw : null;

            $this->ensureSocietyColumns();

            // Same registration → always update the one linked row (never a second INSERT)
            if ($regId !== null) {
                $stmt = $this->db->prepare('SELECT id, code FROM societies WHERE registration_id = ? ORDER BY id ASC LIMIT 1');
                $stmt->execute([$regId]);
                $byReg = $stmt->fetch();
                if ($byReg) {
                    $sid = (int) $byReg['id'];
                    $code = !empty($byReg['code'])
                        ? $byReg['code']
                        : (strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['name']), 0, 4)) . rand(100, 999));
                    $this->update('societies', [
                        'name'           => $data['name'],
                        'code'           => $code,
                        'address'        => $data['address'],
                        'city'           => $data['city']          ?? '',
                        'state'          => $data['state']         ?? '',
                        'country'        => $data['country']       ?? 'India',
                        'pincode'        => $data['pincode']       ?? '',
                        'contact_person' => $data['contact_person'] ?? $data['contactName'] ?? '',
                        'contact_phone'  => $normalizedPhone,
                        'contact_email'  => $emailTrim,
                        'plan'           => $plan,
                        'status'         => 'approved',
                    ], 'id = :id', ['id' => $sid]);
                    $this->update('society_registrations', [
                        'status'      => 'approved',
                        'reviewed_at' => date('Y-m-d H:i:s'),
                    ], 'id = :id', ['id' => $regId]);
                    $this->syncSocietyToReg($sid);
                    Response::success('Society updated successfully', ['society_id' => $sid, 'code' => $code], 200);
                }

                $stmt = $this->db->prepare('SELECT * FROM society_registrations WHERE id = ?');
                $stmt->execute([$regId]);
                $regRow = $stmt->fetch();
                if ($regRow) {
                    $leadPhone = $this->normalizePhone((string) ($regRow['contact_phone'] ?? ''));
                    $matchSid = $this->findSocietyIdForRegistrationLead($regId, $regRow, $leadPhone);
                    if ($matchSid !== null) {
                        $stmt = $this->db->prepare('SELECT code FROM societies WHERE id = ?');
                        $stmt->execute([$matchSid]);
                        $codeRow = $stmt->fetch() ?: [];
                        $code = !empty($codeRow['code'])
                            ? $codeRow['code']
                            : (strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['name']), 0, 4)) . rand(100, 999));
                        $this->update('societies', [
                            'registration_id' => $regId,
                            'name'           => $data['name'],
                            'code'           => $code,
                            'address'        => $data['address'],
                            'city'           => $data['city']          ?? '',
                            'state'          => $data['state']         ?? '',
                            'country'        => $data['country']       ?? 'India',
                            'pincode'        => $data['pincode']       ?? '',
                            'contact_person' => $data['contact_person'] ?? $data['contactName'] ?? '',
                            'contact_phone'  => $normalizedPhone,
                            'contact_email'  => $emailTrim,
                            'plan'           => $plan,
                            'status'         => 'approved',
                        ], 'id = :id', ['id' => $matchSid]);
                        $this->update('society_registrations', [
                            'status'      => 'approved',
                            'reviewed_at' => date('Y-m-d H:i:s'),
                        ], 'id = :id', ['id' => $regId]);
                        $this->syncSocietyToReg($matchSid);
                        Response::success('Society updated successfully', ['society_id' => $matchSid, 'code' => $code], 200);
                    }
                }
            }

            $dupId = $this->findDuplicateSocietyId($data['name'], $emailTrim !== '' ? $emailTrim : null, $normalizedPhone, $rawPhone !== '' ? $rawPhone : null);
            if ($dupId !== null) {
                Response::error('A society with this name, email, or phone already exists (#' . $dupId . ')', 409);
            }

            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['name']), 0, 4)) . rand(100, 999);

            $societyId = $this->insert('societies', [
                'name'           => $data['name'],
                'code'           => $code,
                'address'        => $data['address'],
                'city'           => $data['city']          ?? '',
                'state'          => $data['state']         ?? '',
                'country'        => $data['country']       ?? 'India',
                'pincode'        => $data['pincode']       ?? '',
                'contact_person' => $data['contact_person'] ?? $data['contactName'] ?? '',
                'contact_phone'  => $normalizedPhone,
                'contact_email'  => $emailTrim,
                'plan'           => $plan,
                'status'         => 'approved',
            ]);

            if ($regId !== null) {
                $this->update('society_registrations', [
                    'status'      => 'approved',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ], 'id = :id', ['id' => $regId]);
                $this->update('societies', ['registration_id' => $regId], 'id = :id', ['id' => $societyId]);
            }

            Response::success("Society created successfully", ['society_id' => $societyId, 'code' => $code], 201);
        } catch (Exception $e) {
            Response::error("Failed to create society: " . $e->getMessage(), 500);
        }
    }

    /** Update society fields. Syncs all changes to linked registration row. */
    public function updateSociety($id)
    {
        try {
            $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);

            $allowed = ['name','address','city','state','country','pincode',
                        'contact_person','contact_phone','contact_email',
                        'plan','status','towers','total_flats','gst','pan'];
            $updateData = [];
            foreach ($allowed as $col) {
                $camel = lcfirst(str_replace('_', '', ucwords($col, '_')));
                if (array_key_exists($col, $data))   $updateData[$col] = $data[$col];
                elseif (array_key_exists($camel, $data)) $updateData[$col] = $data[$camel];
            }
            if (empty($updateData)) Response::error("No valid fields provided", 400);

            $this->update('societies', $updateData, 'id = :id', ['id' => $id]);

            // Sync to registration
            $this->syncSocietyToReg((int)$id);

            Response::success("Society updated successfully");
        } catch (Exception $e) {
            Response::error("Failed to update society: " . $e->getMessage(), 500);
        }
    }

    public function approveSociety($societyId)
    {
        try {
            $this->auth->authorize('super_admin');
            $this->update('societies', ['status' => 'approved'], 'id = :id', ['id' => $societyId]);
            $this->syncSocietyToReg((int)$societyId);
            Response::success("Society approved");
        } catch (Exception $e) {
            Response::error("Failed to approve society: " . $e->getMessage(), 500);
        }
    }

    public function suspendSociety($societyId)
    {
        try {
            $this->auth->authorize('super_admin');
            $this->update('societies', ['status' => 'suspended'], 'id = :id', ['id' => $societyId]);
            $this->syncSocietyToReg((int)$societyId);
            Response::success("Society suspended");
        } catch (Exception $e) {
            Response::error("Failed to suspend society: " . $e->getMessage(), 500);
        }
    }

    public function deleteSociety($id)
    {
        try {
            $this->auth->authorize('super_admin');
            $stmt = $this->db->prepare("SELECT id, registration_id FROM societies WHERE id = ?");
            $stmt->execute([$id]);
            $soc = $stmt->fetch();
            if (!$soc) Response::notFound("Society not found");

            $this->delete('societies', 'id = ?', [$id]);

            // If linked to a registration, revert it to under_review so it can be re-processed
            if (!empty($soc['registration_id'])) {
                try {
                    $this->update('society_registrations', [
                        'status'      => 'under_review',
                        'reviewed_at' => date('Y-m-d H:i:s'),
                    ], 'id = :id', ['id' => $soc['registration_id']]);
                } catch (Exception $e) {}
            }

            Response::success("Society deleted successfully");
        } catch (Exception $e) {
            Response::error("Failed to delete society: " . $e->getMessage(), 500);
        }
    }

    // ─── ADMINS ─────────────────────────────────────────────────────────────

    public function createSocietyAdmin($societyId)
    {
        try {
            $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data)) {
                $data = [];
            }

            $name = trim((string) ($data['name'] ?? $data['adminName'] ?? ''));
            $email = trim((string) ($data['email'] ?? $data['adminEmail'] ?? ''));
            $phoneRaw = trim((string) ($data['phone'] ?? $data['adminPhone'] ?? ''));
            $password = (string) ($data['password'] ?? $data['adminPassword'] ?? '');

            $errors = [];
            if ($name === '') {
                $errors[] = 'name is required';
            }
            if ($email === '') {
                $errors[] = 'email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'email must be a valid address';
            }
            if ($phoneRaw === '') {
                $errors[] = 'phone is required';
            }
            if ($password === '') {
                $errors[] = 'password is required';
            } elseif (strlen($password) < 8) {
                $errors[] = 'password must be at least 8 characters';
            }
            if (!empty($errors)) {
                Response::validationError($errors);
            }

            $stmt = $this->db->prepare('SELECT id FROM societies WHERE id = ?');
            $stmt->execute([$societyId]);
            if (!$stmt->fetch()) {
                Response::notFound('Society not found');
            }

            $normalizedPhone = $this->normalizePhone($phoneRaw);

            // Match approveRegistrationLead: same email or normalized/raw phone
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? OR phone = ? OR phone = ?');
            $stmt->execute([$email, $phoneRaw, $normalizedPhone]);
            if ($stmt->fetch()) {
                Response::error('User with this email or phone already exists', 409);
            }

            $appUserId = AppUserIdHelper::generateUnique($this->db);
            $userId = $this->insert('users', [
                'app_user_id' => $appUserId,
                'name'        => $name,
                'email'       => $email,
                'phone'       => $normalizedPhone,
                'password'    => password_hash($password, PASSWORD_DEFAULT),
                'role'        => 'admin',
                'society_id'  => $societyId,
                'status'      => 'active',
            ]);

            // Link admin to society
            $this->update('societies', ['admin_id' => $userId, 'status' => 'approved'], 'id = :id', ['id' => $societyId]);

            Response::success('Admin created', ['user_id' => (int) $userId], 201);
        } catch (Exception $e) {
            Response::error('Failed to create admin: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing society admin's details (name, email, phone, password).
     * The admin must belong to the given society.
     */
    public function updateSocietyAdmin($societyId)
    {
        try {
            $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data)) {
                $data = [];
            }

            $name = trim((string) ($data['name'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $phoneRaw = trim((string) ($data['phone'] ?? ''));
            $password = (string) ($data['password'] ?? '');

            // Validate required fields
            $errors = [];
            if ($name === '') $errors[] = 'name is required';
            if ($email === '') $errors[] = 'email is required';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email must be a valid address';
            if ($phoneRaw === '') $errors[] = 'phone is required';
            if ($password !== '' && strlen($password) < 8) $errors[] = 'password must be at least 8 characters';
            if (!empty($errors)) Response::validationError($errors);

            $normalizedPhone = $this->normalizePhone($phoneRaw);

            // Find the admin for this society
            $stmt = $this->db->prepare("SELECT id FROM users WHERE society_id = ? AND role = 'admin' ORDER BY id ASC LIMIT 1");
            $stmt->execute([$societyId]);
            $admin = $stmt->fetch();
            if (!$admin) Response::notFound("No admin found for this society");

            $adminId = $admin['id'];

            // Check for conflicts with other users (exclude current admin)
            $stmt = $this->db->prepare("SELECT id FROM users WHERE (email = ? OR phone = ? OR phone = ?) AND id != ?");
            $stmt->execute([$email, $phoneRaw, $normalizedPhone, $adminId]);
            if ($stmt->fetch()) {
                Response::error("Another user with this email or phone already exists", 409);
            }

            // Build update data
            $updateData = [
                'name'  => $name,
                'email' => $email,
                'phone' => $normalizedPhone,
            ];
            if ($password !== '') {
                $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $this->update('users', $updateData, 'id = :id', ['id' => $adminId]);

            // Also sync back to the society's contact fields for consistency
            $this->update('societies', [
                'contact_person' => $name,
                'contact_email'  => $email,
                'contact_phone'  => $normalizedPhone,
            ], 'id = :id', ['id' => $societyId]);

            Response::success("Admin updated successfully", [
                'admin_id' => (int) $adminId,
                'name'     => $name,
                'email'    => $email,
                'phone'    => $normalizedPhone,
            ]);
        } catch (Exception $e) {
            Response::error("Failed to update admin: " . $e->getMessage(), 500);
        }
    }

    public function getAdmins()
    {
        try {
            $this->auth->authorize('super_admin');
            $stmt = $this->db->query(
                "SELECT u.id, u.name, u.email, u.phone, u.role, u.society_id as societyId, u.status, u.created_at as createdAt
                 FROM users u WHERE u.role = 'admin' ORDER BY u.created_at DESC"
            );
            $admins = $stmt->fetchAll();

            foreach ($admins as &$admin) {
                $admin['isActive'] = ($admin['status'] === 'active');
                $admin['society'] = null;
                if ($admin['societyId']) {
                    try {
                        $stmt2 = $this->db->prepare("SELECT id, name, code, status FROM societies WHERE id = ?");
                        $stmt2->execute([$admin['societyId']]);
                        $admin['society'] = $stmt2->fetch() ?: null;
                    } catch (Exception $e) {
                        $stmt2 = $this->db->prepare("SELECT id, name FROM societies WHERE id = ?");
                        $stmt2->execute([$admin['societyId']]);
                        $admin['society'] = $stmt2->fetch() ?: null;
                        if ($admin['society']) {
                            $admin['society']['code'] = 'N/A';
                            $admin['society']['status'] = 'approved';
                        }
                    }
                }
            }

            Response::success("Admins retrieved", $admins);
        } catch (Exception $e) {
            Response::error("Failed to retrieve admins: " . $e->getMessage(), 500);
        }
    }

    public function toggleAdmin($id)
    {
        try {
            $this->auth->authorize('super_admin');
            $stmt = $this->db->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$id]);
            $admin = $stmt->fetch();
            if (!$admin) Response::notFound("Admin not found");

            $newStatus = ($admin['status'] === 'active') ? 'inactive' : 'active';
            $this->update('users', ['status' => $newStatus], 'id = :id', ['id' => $id]);

            Response::success("Admin status updated", [
                'id'       => $id,
                'status'   => $newStatus,
                'isActive' => ($newStatus === 'active'),
            ]);
        } catch (Exception $e) {
            Response::error("Failed to toggle admin: " . $e->getMessage(), 500);
        }
    }
}
