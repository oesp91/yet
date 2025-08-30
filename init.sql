CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user'
);

INSERT INTO users (username, password, email, role) VALUES
('admin', 'fake_pw', 'admin@hcamp.com', 'admin'),
('guest', 'guest', 'guest@hcamp.com', 'user');