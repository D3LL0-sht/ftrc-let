-- ============================================================
-- FTRC LET Review System — MySQL Schema (GoogieHost)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS topic_analytics;
DROP TABLE IF EXISTS ai_explanations;
DROP TABLE IF EXISTS session_questions;
DROP TABLE IF EXISTS exam_sessions;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS topics;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. USERS
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. TOPICS
-- ------------------------------------------------------------
CREATE TABLE topics (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  description TEXT
);

-- ------------------------------------------------------------
-- 3. QUESTIONS
-- ------------------------------------------------------------
CREATE TABLE questions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  topic_id       INT NOT NULL,
  question_text  TEXT NOT NULL,
  choice_a       TEXT NOT NULL,
  choice_b       TEXT NOT NULL,
  choice_c       TEXT NOT NULL,
  choice_d       TEXT NOT NULL,
  correct_answer CHAR(1) NOT NULL,
  difficulty     ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  explanation    TEXT,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 4. EXAM SESSIONS
-- ------------------------------------------------------------
CREATE TABLE exam_sessions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  mode           ENUM('mock','drill','topic') NOT NULL DEFAULT 'mock',
  time_limit_sec INT,
  time_used_sec  INT,
  started_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at   DATETIME,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 5. SESSION QUESTIONS
-- ------------------------------------------------------------
CREATE TABLE session_questions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  session_id     INT NOT NULL,
  question_id    INT NOT NULL,
  user_answer    CHAR(1),
  is_correct     TINYINT(1),
  time_spent_sec INT,
  FOREIGN KEY (session_id)  REFERENCES exam_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- ------------------------------------------------------------
-- 6. AI EXPLANATIONS
-- ------------------------------------------------------------
CREATE TABLE ai_explanations (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  session_question_id INT NOT NULL,
  ai_response         TEXT NOT NULL,
  generated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_question_id) REFERENCES session_questions(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 7. TOPIC ANALYTICS
-- ------------------------------------------------------------
CREATE TABLE topic_analytics (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  topic_id       INT NOT NULL,
  total_attempts INT NOT NULL DEFAULT 0,
  correct_count  INT NOT NULL DEFAULT 0,
  accuracy_pct   DECIMAL(5,2) GENERATED ALWAYS AS (
                   CASE WHEN total_attempts = 0 THEN 0
                   ELSE ROUND((correct_count / total_attempts) * 100, 2)
                   END
                 ) STORED,
  last_updated   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_topic (user_id, topic_id),
  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- INDEXES
-- ------------------------------------------------------------
CREATE INDEX idx_questions_topic      ON questions(topic_id);
CREATE INDEX idx_questions_difficulty ON questions(difficulty);
CREATE INDEX idx_sessions_user        ON exam_sessions(user_id);
CREATE INDEX idx_session_q_session    ON session_questions(session_id);
CREATE INDEX idx_session_q_question   ON session_questions(question_id);
CREATE INDEX idx_analytics_user_topic ON topic_analytics(user_id, topic_id);

-- ------------------------------------------------------------
-- SEED: LET English Specialization Topics
-- ------------------------------------------------------------
INSERT INTO topics (name, description) VALUES
  ('Literature',         'Philippine and world literature; literary criticism and history'),
  ('Grammar & Usage',    'Syntax, morphology, grammar rules, and correct usage'),
  ('Linguistics',        'Language structure, phonology, semantics, and pragmatics'),
  ('Communication',      'Oral and written communication skills and strategies'),
  ('Content & Pedagogy', 'Teaching methods, curriculum design, and assessment in English');