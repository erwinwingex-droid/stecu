-- SQL to add feedback_responses table
CREATE TABLE IF NOT EXISTS feedback_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  feedback_id INT NOT NULL,
  responder_id INT NOT NULL,
  responder_role VARCHAR(32) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (feedback_id),
  INDEX (responder_id),
  CONSTRAINT fk_feedback_resp_feedback FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
