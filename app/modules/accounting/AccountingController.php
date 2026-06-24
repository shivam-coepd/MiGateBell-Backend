<?php
require_once __DIR__.'/../../core/BaseController.php';
require_once __DIR__.'/../../helpers/notification_helper.php';

class AccountingController extends BaseController {
  
  public function createChargeHead() {
    try {
      // Only admins can create charge heads
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['name', 'charge_type']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate charge type
      $allowedChargeTypes = ['fixed', 'per_area', 'per_person', 'slab'];
      if (!in_array($data['charge_type'], $allowedChargeTypes)) {
        Response::error("Invalid charge type. Allowed values: " . implode(', ', $allowedChargeTypes));
      }
      
      // Validate numeric fields
      if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] < 0)) {
        Response::error("Amount must be a non-negative number");
      }
      
      if (isset($data['gst_rate']) && (!is_numeric($data['gst_rate']) || $data['gst_rate'] < 0 || $data['gst_rate'] > 100)) {
        Response::error("GST rate must be a number between 0 and 100");
      }
      
      if (isset($data['is_active']) && !is_numeric($data['is_active'])) {
        Response::error("is_active must be a numeric value");
      }
      
      // Insert charge head
      $chargeHeadId = $this->insert('charge_heads', [
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'charge_type' => $data['charge_type'],
        'amount' => $data['amount'] ?? 0,
        'slab_details' => isset($data['slab_details']) ? json_encode($data['slab_details']) : null,
        'gst_rate' => $data['gst_rate'] ?? 0,
        'is_active' => $data['is_active'] ?? 1,
        'society_id' => $user['society_id']
      ]);
      
      Response::success("Charge head created successfully", ['charge_head_id' => $chargeHeadId], 201);
      
    } catch(Exception $e) {
      error_log("Create charge head error: " . $e->getMessage());
      Response::error("Failed to create charge head: " . $e->getMessage(), 500);
    }
  }
  
  public function getChargeHeads() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query
      $whereClause = "WHERE ch.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      if ($isActive !== null) {
        $whereClause .= " AND ch.is_active = :is_active";
        $params['is_active'] = $isActive;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM charge_heads ch {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get charge heads
      $sql = "
        SELECT ch.*, 
               CASE 
                 WHEN ch.charge_type = 'slab' THEN ch.slab_details 
                 ELSE NULL 
               END as slab_details
        FROM charge_heads ch
        {$whereClause}
        ORDER BY ch.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $chargeHeads = $stmt->fetchAll();
      
      // Decode slab details
      foreach ($chargeHeads as &$chargeHead) {
        if ($chargeHead['slab_details']) {
          $chargeHead['slab_details'] = json_decode($chargeHead['slab_details'], true);
        }
      }
      
      $this->sendPaginatedResponse($chargeHeads, $total, $pagination, "Charge heads retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get charge heads error: " . $e->getMessage());
      Response::error("Failed to retrieve charge heads: " . $e->getMessage(), 500);
    }
  }
  
  public function createInvoice() {
    try {
      // Only admins can create invoices
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['flat_id', 'invoice_date']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      if (empty($data['items']) || !is_array($data['items'])) {
        Response::error("Invoice items are required");
      }
      
      // Validate date formats
      if (!empty($data['invoice_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['invoice_date'])) {
        Response::error("Invalid invoice date format. Expected YYYY-MM-DD");
      }
      
      if (!empty($data['due_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
        Response::error("Invalid due date format. Expected YYYY-MM-DD");
      }
      
      // Check if flat exists and belongs to the same society
      $stmt = $this->db->prepare("SELECT id FROM flats WHERE id = ? AND society_id = ?");
      $stmt->execute([$data['flat_id'], $user['society_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("Flat not found in your society");
      }
      
      // Check if resident exists and belongs to the same society when provided
      if (!empty($data['resident_id'])) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ?");
        $stmt->execute([$data['resident_id'], $user['society_id']]);
        if (!$stmt->fetch()) {
          Response::notFound("Resident not found in your society");
        }
      }
      
      // Calculate totals
      $totalAmount = 0;
      $totalGst = 0;
      
      foreach ($data['items'] as $item) {
        $itemErrors = $this->validateRequiredFields($item, ['charge_head_id', 'unit_price']);
        if (!empty($itemErrors)) {
          Response::validationError($itemErrors);
        }
        
        // Validate numeric fields in item
        if (!is_numeric($item['unit_price']) || $item['unit_price'] < 0) {
          Response::error("Unit price must be a non-negative number");
        }
        
        if (isset($item['quantity']) && (!is_numeric($item['quantity']) || $item['quantity'] < 0)) {
          Response::error("Quantity must be a non-negative number");
        }
        
        if (isset($item['gst_rate']) && (!is_numeric($item['gst_rate']) || $item['gst_rate'] < 0 || $item['gst_rate'] > 100)) {
          Response::error("GST rate must be a number between 0 and 100");
        }
        
        $quantity = $item['quantity'] ?? 1;
        $itemTotal = $quantity * $item['unit_price'];
        $itemGst = $itemTotal * ($item['gst_rate'] ?? 0) / 100;
        
        $totalAmount += $itemTotal;
        $totalGst += $itemGst;
      }
      
      // Generate invoice number
      $invoiceNumber = $this->generateInvoiceNumber($user['society_id']);
      
      // Insert invoice
      $invoiceId = $this->insert('invoices', [
        'invoice_number' => $invoiceNumber,
        'flat_id' => $data['flat_id'],
        'resident_id' => $data['resident_id'] ?? null,
        'society_id' => $user['society_id'],
        'invoice_date' => $data['invoice_date'],
        'due_date' => $data['due_date'] ?? null,
        'total_amount' => $totalAmount,
        'total_gst' => $totalGst,
        'total_discount' => $data['total_discount'] ?? 0,
        'arrears_amount' => $data['arrears_amount'] ?? 0,
        'fine_amount' => $data['fine_amount'] ?? 0,
        'status' => 'draft',
        'notes' => $data['notes'] ?? null,
        'created_by' => $user['uid']
      ]);
      
      // Insert invoice items
      foreach ($data['items'] as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $itemGst = $itemTotal * ($item['gst_rate'] ?? 0) / 100;
        
        $this->insert('invoice_items', [
          'invoice_id' => $invoiceId,
          'charge_head_id' => $item['charge_head_id'],
          'description' => $item['description'] ?? null,
          'quantity' => $item['quantity'] ?? 1,
          'unit_price' => $item['unit_price'],
          'gst_rate' => $item['gst_rate'] ?? 0,
          'gst_amount' => $itemGst,
          'total_amount' => $itemTotal
        ]);
      }
      
      // Notify Resident (if mapped)
      if (!empty($data['resident_id'])) {
          $notificationHelper = new NotificationHelper();
          $notificationHelper->sendPushNotification(
              $data['resident_id'],
              "New Invoice Generated",
              "Invoice {$invoiceNumber} for amount ₹{$totalAmount} has been generated.",
              ['invoice_id' => $invoiceId],
              'invoice_generated',
              $invoiceId,
              '/accounting/invoices'
          );
      } else {
          // Find resident for flat
          $stmt = $this->db->prepare("SELECT owner_id, tenant_id FROM flats WHERE id = ?");
          $stmt->execute([$data['flat_id']]);
          $flat = $stmt->fetch();
          if ($flat) {
              $residentToNotify = $flat['tenant_id'] ?? $flat['owner_id'];
              if ($residentToNotify) {
                  $notificationHelper = new NotificationHelper();
                  $notificationHelper->sendPushNotification(
                      $residentToNotify,
                      "New Invoice Generated",
                      "Invoice {$invoiceNumber} for amount ₹{$totalAmount} has been generated.",
                      ['invoice_id' => $invoiceId],
                      'invoice_generated',
                      $invoiceId,
                      '/accounting/invoices'
                  );
              }
          }
      }

      Response::success("Invoice created successfully", ['invoice_id' => $invoiceId], 201);
      
    } catch(Exception $e) {
      error_log("Create invoice error: " . $e->getMessage());
      Response::error("Failed to create invoice: " . $e->getMessage(), 500);
    }
  }
  
  private function generateInvoiceNumber($societyId) {
    try {
      // Get the last invoice number for this society
      $stmt = $this->db->prepare("
        SELECT invoice_number 
        FROM invoices 
        WHERE society_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
      ");
      $stmt->execute([$societyId]);
      $lastInvoice = $stmt->fetch();
      
      if ($lastInvoice) {
        // Extract number part and increment
        $lastNumber = intval(substr($lastInvoice['invoice_number'], -6));
        $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
      } else {
        // First invoice
        $newNumber = '000001';
      }
      
      // Format: INV-[SOCIETY_ID]-[NUMBER]
      return "INV-{$societyId}-{$newNumber}";
    } catch(Exception $e) {
      // Fallback to timestamp if there's an error
      return "INV-" . time();
    }
  }
  
  public function getInvoices() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $status = isset($_GET['status']) ? $_GET['status'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      $joinClause = "LEFT JOIN flats f ON i.flat_id = f.id";

      // Build query based on user role
      $whereClause = "WHERE i.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      // Residents can only see their own invoices or invoices for their flat
      if ($user['role'] === 'resident') {
        $whereClause .= " AND (i.resident_id = :rid1 OR f.owner_id = :rid2 OR f.tenant_id = :rid3)";
        $params['rid1'] = $user['uid'];
        $params['rid2'] = $user['uid'];
        $params['rid3'] = $user['uid'];
      }
      
      // Filter by status if provided
      if ($status) {
        $whereClause .= " AND i.status = :status";
        $params['status'] = $status;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM invoices i {$joinClause} {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get invoices
      $sql = "
        SELECT i.*, f.flat_number, u.name as resident_name,
               (SELECT payment_method FROM payments WHERE invoice_id = i.id AND transaction_status = 'success' ORDER BY created_at DESC LIMIT 1) as payment_method,
               (SELECT created_at FROM payments WHERE invoice_id = i.id AND transaction_status = 'success' ORDER BY created_at DESC LIMIT 1) as paid_date
        FROM invoices i
        {$joinClause}
        LEFT JOIN users u ON i.resident_id = u.id
        {$whereClause}
        ORDER BY i.invoice_date DESC, i.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $invoices = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($invoices, $total, $pagination, "Invoices retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get invoices error: " . $e->getMessage());
      Response::error("Failed to retrieve invoices: " . $e->getMessage(), 500);
    }
  }
  
  public function getInvoiceById($id) {
    try {
      $user = $this->auth->authenticate();
      
      $joinClause = "LEFT JOIN flats f ON i.flat_id = f.id";
      
      // Build query based on user role
      $whereClause = "WHERE i.id = :id AND i.society_id = :society_id";
      $params = ['id' => $id, 'society_id' => $user['society_id']];
      
      // Residents can only see their own invoices or invoices for their flat
      if ($user['role'] === 'resident') {
        $whereClause .= " AND (i.resident_id = :rid1 OR f.owner_id = :rid2 OR f.tenant_id = :rid3)";
        $params['rid1'] = $user['uid'];
        $params['rid2'] = $user['uid'];
        $params['rid3'] = $user['uid'];
      }
      
      // Get invoice
      $sql = "
        SELECT i.*, f.flat_number, u.name as resident_name, cb.name as created_by_name,
               (SELECT payment_method FROM payments WHERE invoice_id = i.id AND transaction_status = 'success' ORDER BY created_at DESC LIMIT 1) as payment_method,
               (SELECT created_at FROM payments WHERE invoice_id = i.id AND transaction_status = 'success' ORDER BY created_at DESC LIMIT 1) as paid_date
        FROM invoices i
        {$joinClause}
        LEFT JOIN users u ON i.resident_id = u.id
        LEFT JOIN users cb ON i.created_by = cb.id
        {$whereClause}
      ";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      $invoice = $stmt->fetch();
      
      if (!$invoice) {
        Response::notFound("Invoice not found or access denied");
      }
      
      // Get invoice items
      $stmt = $this->db->prepare("
        SELECT ii.*, ch.name as charge_head_name
        FROM invoice_items ii
        LEFT JOIN charge_heads ch ON ii.charge_head_id = ch.id
        WHERE ii.invoice_id = ?
        ORDER BY ii.id ASC
      ");
      $stmt->execute([$id]);
      $invoice['items'] = $stmt->fetchAll();
      
      Response::success("Invoice retrieved successfully", $invoice);
      
    } catch(Exception $e) {
      error_log("Get invoice error: " . $e->getMessage());
      Response::error("Failed to retrieve invoice: " . $e->getMessage(), 500);
    }
  }
  
  public function processPayment() {
    try {
      // Residents can make payments for their invoices
      // Admins can process payments for any invoice
      $user = $this->auth->authenticate();
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['invoice_id', 'amount', 'payment_method']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate payment method
      $allowedMethods = ['upi', 'net_banking', 'credit_card', 'debit_card', 'cash', 'cheque', 'bank_transfer'];
      if (!in_array($data['payment_method'], $allowedMethods)) {
        Response::error("Invalid payment method. Allowed values: " . implode(', ', $allowedMethods));
      }
      
      // Validate numeric fields
      if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
        Response::error("Payment amount must be a positive number");
      }
      
      // Check if invoice exists and belongs to the same society
      $stmt = $this->db->prepare("
        SELECT i.*, f.flat_number, f.owner_id, f.tenant_id
        FROM invoices i
        LEFT JOIN flats f ON i.flat_id = f.id
        WHERE i.id = ? AND i.society_id = ?
      ");
      $stmt->execute([$data['invoice_id'], $user['society_id']]);
      $invoice = $stmt->fetch();
      
      if (!$invoice) {
        Response::notFound("Invoice not found");
      }
      
      // Check permissions
      if ($user['role'] === 'resident' && 
          $invoice['resident_id'] != $user['uid'] && 
          $invoice['owner_id'] != $user['uid'] && 
          $invoice['tenant_id'] != $user['uid']) {
        Response::forbidden("You can only make payments for your own invoices");
      }
      
      // Check if amount is valid
      if ($data['amount'] <= 0) {
        Response::error("Payment amount must be greater than zero");
      }
      
      // Generate payment reference
      $paymentReference = "PAY-" . time() . "-" . rand(1000, 9999);
      
      // Insert payment
      $paymentId = $this->insert('payments', [
        'payment_reference' => $paymentReference,
        'invoice_id' => $data['invoice_id'],
        'resident_id' => $invoice['resident_id'] ?? $user['uid'],
        'society_id' => $user['society_id'],
        'payment_method' => $data['payment_method'],
        'payment_gateway' => $data['payment_gateway'] ?? null,
        'amount' => $data['amount'],
        'transaction_id' => $data['transaction_id'] ?? null,
        'transaction_status' => 'success',
        'notes' => $data['notes'] ?? null
      ]);
      
      // Update invoice status to paid
      $this->update('invoices', ['status' => 'paid'], 'id = :id', ['id' => $data['invoice_id']]);
      
      // Create receipt
      $receiptNumber = $this->generateReceiptNumber($user['society_id']);
      
      $receiptId = $this->insert('receipts', [
        'receipt_number' => $receiptNumber,
        'payment_id' => $paymentId,
        'invoice_id' => $data['invoice_id'],
        'resident_id' => $invoice['resident_id'] ?? $user['uid'],
        'society_id' => $user['society_id'],
        'amount' => $data['amount'],
        'receipt_date' => date('Y-m-d')
      ]);
      
      // Notify Admin
      $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' AND society_id = ?");
      $stmt->execute([$user['society_id']]);
      $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
      
      if (!empty($admins)) {
          $notificationHelper = new NotificationHelper();
          $notificationHelper->sendBulkNotifications(
              $admins,
              "Payment Received",
              "A payment of ₹{$data['amount']} was received for Invoice #{$data['invoice_id']}.",
              ['payment_id' => $paymentId],
              'payment_received',
              $paymentId,
              '/admin/accounting/payments'
          );
      }
      
      // Notify Resident
      if ($invoice['resident_id']) {
          $notificationHelper = new NotificationHelper();
          $notificationHelper->sendPushNotification(
              $invoice['resident_id'],
              "Payment Successful",
              "Your payment of ₹{$data['amount']} was received successfully.",
              ['receipt_id' => $receiptId],
              'payment_received',
              $receiptId,
              '/accounting/receipts'
          );
      }

      Response::success("Payment processed successfully", [
        'payment_id' => $paymentId,
        'receipt_id' => $receiptId,
        'payment_reference' => $paymentReference,
        'receipt_number' => $receiptNumber
      ], 201);
      
    } catch(Exception $e) {
      error_log("Process payment error: " . $e->getMessage());
      Response::error("Failed to process payment: " . $e->getMessage(), 500);
    }
  }
  
  private function generateReceiptNumber($societyId) {
    try {
      // Get the last receipt number for this society
      $stmt = $this->db->prepare("
        SELECT receipt_number 
        FROM receipts 
        WHERE society_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
      ");
      $stmt->execute([$societyId]);
      $lastReceipt = $stmt->fetch();
      
      if ($lastReceipt) {
        // Extract number part and increment
        $lastNumber = intval(substr($lastReceipt['receipt_number'], -6));
        $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
      } else {
        // First receipt
        $newNumber = '000001';
      }
      
      // Format: REC-[SOCIETY_ID]-[NUMBER]
      return "REC-{$societyId}-{$newNumber}";
    } catch(Exception $e) {
      // Fallback to timestamp if there's an error
      return "REC-" . time();
    }
  }
  
  public function updateInvoiceStatus($id) {
    try {
      // Only admins can update invoice status
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      if (empty($data['status'])) {
        Response::error("Status is required");
      }
      
      // Validate status against allowed ENUM values
      $allowedStatuses = ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      // Check if invoice exists and belongs to the same society
      $stmt = $this->db->prepare("SELECT id FROM invoices WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $invoice = $stmt->fetch();
      
      if (!$invoice) {
        Response::notFound("Invoice not found");
      }
      
      // Update invoice status
      $updated = $this->update('invoices', [
        'status' => $data['status']
      ], 'id = :id', ['id' => $id]);
      
      if ($updated === 0) {
        Response::error("Failed to update invoice status", 500);
      }
      
      Response::success("Invoice status updated successfully");
      
    } catch(Exception $e) {
      error_log("Update invoice status error: " . $e->getMessage());
      Response::error("Failed to update invoice status: " . $e->getMessage(), 500);
    }
  }
  
  public function updatePaymentTransactionStatus($id) {
    try {
      // Only admins can update payment transaction status
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      if (empty($data['transaction_status'])) {
        Response::error("Transaction status is required");
      }
      
      // Validate transaction_status against allowed ENUM values
      $allowedStatuses = ['pending', 'success', 'failed', 'refunded'];
      if (!in_array($data['transaction_status'], $allowedStatuses)) {
        Response::error("Invalid transaction status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      // Check if payment exists and belongs to the same society
      $stmt = $this->db->prepare("SELECT id FROM payments WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $payment = $stmt->fetch();
      
      if (!$payment) {
        Response::notFound("Payment not found");
      }
      
      // Update payment transaction status
      $updated = $this->update('payments', [
        'transaction_status' => $data['transaction_status']
      ], 'id = :id', ['id' => $id]);
      
      if ($updated === 0) {
        Response::error("Failed to update payment transaction status", 500);
      }
      
      Response::success("Payment transaction status updated successfully");
      
    } catch(Exception $e) {
      error_log("Update payment transaction status error: " . $e->getMessage());
      Response::error("Failed to update payment transaction status: " . $e->getMessage(), 500);
    }
  }

  /**
   * Delete an invoice (admin only)
   */
  public function deleteInvoice($id) {
    try {
      $user = $this->auth->authorize('admin');
      // Delete invoice items first
      $this->delete('invoice_items', 'invoice_id = ?', [$id]);
      // Delete the invoice
      $deleted = $this->delete('invoices', 'id = ? AND society_id = ?', [$id, $user['society_id']]);
      if ($deleted === 0) {
        Response::error("Failed to delete invoice", 500);
      }
      Response::success("Invoice deleted");
    } catch (Exception $e) {
      error_log("Delete invoice error: " . $e->getMessage());
      Response::error("Failed to delete invoice: " . $e->getMessage(), 500);
    }
  }

}