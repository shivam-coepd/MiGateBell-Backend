ALTER TABLE users
    ADD COLUMN cover_image_url VARCHAR(255) NULL DEFAULT NULL COMMENT 'URL for user cover/banner image',
    ADD COLUMN resident_type ENUM('owner', 'tenant', 'family_member', 'other') NULL DEFAULT NULL COMMENT 'Type of resident',
    ADD COLUMN bio TEXT NULL DEFAULT NULL COMMENT 'Short biography or about section',
    ADD COLUMN profession VARCHAR(150) NULL DEFAULT NULL COMMENT 'Profession or work',
    ADD COLUMN hometown VARCHAR(150) NULL DEFAULT NULL COMMENT 'Hometown or place of origin';