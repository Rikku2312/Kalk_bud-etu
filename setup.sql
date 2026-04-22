-- ============================================================
-- setup.sql — Skrypt tworzenia bazy danych Kalk Budget
-- ============================================================

CREATE DATABASE IF NOT EXISTS kalk_budget
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kalk_budget;

-- Tabela kategorii
CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    type       ENUM('income','expense') NOT NULL,
    icon       VARCHAR(10)  DEFAULT '💰',
    color      VARCHAR(7)   DEFAULT '#6366f1',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela transakcji
CREATE TABLE IF NOT EXISTS transactions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    type        ENUM('income','expense') NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    description VARCHAR(255),
    date        DATE          NOT NULL,
    note        TEXT,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela budżetów miesięcznych
CREATE TABLE IF NOT EXISTS budgets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    month       INT NOT NULL,   -- 1-12
    year        INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_budget (category_id, month, year),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela celów oszczędnościowych
CREATE TABLE IF NOT EXISTS savings_goals (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    saved_amount  DECIMAL(12,2) DEFAULT 0.00,
    deadline     DATE,
    icon         VARCHAR(10)  DEFAULT '🎯',
    color        VARCHAR(7)   DEFAULT '#10b981',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dane przykładowe — kategorie
INSERT IGNORE INTO categories (id, name, type, icon, color) VALUES
(1,  'Wynagrodzenie',   'income',  '💼', '#10b981'),
(2,  'Freelance',       'income',  '💻', '#06b6d4'),
(3,  'Inwestycje',      'income',  '📈', '#8b5cf6'),
(4,  'Inne przychody',  'income',  '💰', '#f59e0b'),
(5,  'Jedzenie',        'expense', '🍕', '#ef4444'),
(6,  'Transport',       'expense', '🚗', '#f97316'),
(7,  'Mieszkanie',      'expense', '🏠', '#eab308'),
(8,  'Rozrywka',        'expense', '🎮', '#a855f7'),
(9,  'Zdrowie',         'expense', '💊', '#ec4899'),
(10, 'Ubrania',         'expense', '👗', '#14b8a6'),
(11, 'Edukacja',        'expense', '📚', '#3b82f6'),
(12, 'Inne wydatki',    'expense', '🛒', '#6b7280');
