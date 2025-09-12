-- fix permessi otp token
ALTER TABLE `zz_otp_tokens` CHANGE `permessi` `permessi` ENUM('r','rw','ra','rwa') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;