-- Import into a new project database. Existing tables are not overwritten.
CREATE DATABASE IF NOT EXISTS fitness_tracker
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitness_tracker;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE exercise_categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE exercises (
    exercise_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    CONSTRAINT fk_exercise_category FOREIGN KEY (category_id)
        REFERENCES exercise_categories(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE workouts (
    workout_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    workout_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, workout_date),
    CONSTRAINT fk_workout_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- One row records one performed set or one timed exercise segment.
-- Weight is in kilograms; duration is in seconds; distance is in kilometers.
CREATE TABLE workout_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_id INT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NOT NULL,
    set_number SMALLINT UNSIGNED NOT NULL,
    repetitions SMALLINT UNSIGNED NULL,
    weight_kg DECIMAL(7,2) NULL,
    duration_seconds INT UNSIGNED NULL,
    distance_km DECIMAL(8,3) NULL,
    notes TEXT NULL,
    CONSTRAINT uq_workout_exercise_set UNIQUE (workout_id, exercise_id, set_number),
    CONSTRAINT chk_set_number CHECK (set_number > 0),
    CONSTRAINT chk_repetitions CHECK (repetitions IS NULL OR repetitions > 0),
    CONSTRAINT chk_weight CHECK (weight_kg IS NULL OR weight_kg >= 0),
    CONSTRAINT chk_duration CHECK (duration_seconds IS NULL OR duration_seconds > 0),
    CONSTRAINT chk_distance CHECK (distance_km IS NULL OR distance_km > 0),
    CONSTRAINT chk_measurement CHECK (repetitions IS NOT NULL OR duration_seconds IS NOT NULL OR distance_km IS NOT NULL),
    CONSTRAINT fk_log_workout FOREIGN KEY (workout_id)
        REFERENCES workouts(workout_id) ON DELETE CASCADE,
    CONSTRAINT fk_log_exercise FOREIGN KEY (exercise_id)
        REFERENCES exercises(exercise_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
