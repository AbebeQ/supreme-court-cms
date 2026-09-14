DROP TABLE IF EXISTS audit_log CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS hearings CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS documents CASCADE;
DROP TABLE IF EXISTS parties CASCADE;
DROP TABLE IF EXISTS cases CASCADE;
--enums type
CREATE TYPE user_role AS ENUM ('ADMIN', 'JUDGE', 'CLERK', 'ADVOCATE', 'LITIGANT');
CREATE TYPE case_status AS ENUM ('FILED', 'HEARING', 'PENDING', 'JUDGMENT', 'CLOSED', 'APPEALED');
CREATE TYPE case_type AS ENUM ('CIVIL', 'CRIMINAL', 'FAMILY', 'LABOR', 'COMMERCIAL', 'CONSTITUTIONAL', 'ADMINISTRATIVE', 'ENVIRONMENTAL', 'INTELLECTUAL_PROPERTY', 'TAX', 'BANKRUPTCY', 'MARITIME', 'INTERNATIONAL', 'APPEAL', 'WRIT', 'OTHER');
CREATE TYPE party_type AS ENUM ('PLAINTIFF', 'DEFENDANT', 'APPELLANT', 'RESPONDENT', 'WITNESS', 'EXPERT', 'INTERVENOR', 'OTHER');

--users table
Create table users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role user_role NOT NULL default 'LITIGANT',
    phone VARCHAR(20),
    address TEXT,
    fayda_number VARCHAR(50) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--cases table
Create table cases (
    id SERIAL PRIMARY KEY,
    case_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
     case_type case_type NOT NULL,
    status case_status NOT NULL default 'FILED',
   assigned_judge INT REFERENCES users(id) ON DELETE SET NULL,
   judgment TEXT,
    filing_date DATE NOT NULL,
    hearing_date DATE,
    judgment_date DATE,
    closed_date DATE,
    created_by INT REFERENCES users(id) ON DELETE SET NULL,
    updated_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--parties table
Create table parties (id SERIAL PRIMARY KEY,
    case_id INT REFERENCES cases(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    advocate_name VARCHAR(255),
    party_type party_type NOT NULL,
    contact_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
---documents table
Create table documents (
    id SERIAL PRIMARY KEY,
    case_id INT REFERENCES cases(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT REFERENCES users(id) ON DELETE SET NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
---hearings table
Create table hearings (
    id SERIAL PRIMARY KEY,
    case_id INT REFERENCES cases(id) ON DELETE CASCADE,
    hearing_date TIMESTAMP NOT NULL,
    hearing_time TIME NOT NULL,
    Courtroom VARCHAR(150),
    hearing_type VARCHAR(100),
    judge_id INT REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    is_virtual BOOLEAN DEFAULT FALSE,
    is_completed BOOLEAN DEFAULT FALSE,
    created_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
--orders table
Create table orders (
    id SERIAL PRIMARY KEY,
    case_id INT REFERENCES cases(id) ON DELETE CASCADE,
    order_date TIMESTAMP NOT NULL,
    order_type VARCHAR(100),
    description TEXT,
    issued_by INT REFERENCES users(id) ON DELETE SET NULL,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
--audit_log table
Create table audit_log (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details JSONB
);
--indexes
CREATE INDEX idx_cases_case_number ON cases(case_number);
CREATE INDEX idx_cases_status ON cases(status);
CREATE INDEX idx_parties_case_id ON parties(case_id);
CREATE INDEX idx_documents_case_id ON documents(case_id);
CREATE INDEX idx_hearings_case_id ON hearings(case_id);
CREATE INDEX idx_orders_case_id ON orders(case_id);
CREATE INDEX idx_audit_log_user_id ON audit_log(user_id);
CREATE INDEX idx_audit_log_timestamp ON audit_log(timestamp);

-- Seed: sample role accounts (password = admin123)
INSERT INTO users (username, email, password_hash, full_name, role, phone, address, fayda_number, is_active) VALUES
('admin', 'admin@example.com', '$2y$10$PAd29QwTpPA2YPtTeNH9i.2AlBJjMVt/68mZpMbEHFP.VJAz2SLXe', 'Admin User', 'ADMIN', '+251900000001', 'Admin Street 1', 'FAYDA-ADMIN-001', TRUE),
('judge', 'judge@example.com', '$2y$10$PAd29QwTpPA2YPtTeNH9i.2AlBJjMVt/68mZpMbEHFP.VJAz2SLXe', 'Judge User', 'JUDGE', '+251900000002', 'Judge Street 2', 'FAYDA-JUDGE-001', TRUE),
('clerk', 'clerk@example.com', '$2y$10$PAd29QwTpPA2YPtTeNH9i.2AlBJjMVt/68mZpMbEHFP.VJAz2SLXe', 'Clerk User', 'CLERK', '+251900000003', 'Clerk Street 3', 'FAYDA-CLERK-001', TRUE),
('advocate', 'advocate@example.com', '$2y$10$PAd29QwTpPA2YPtTeNH9i.2AlBJjMVt/68mZpMbEHFP.VJAz2SLXe', 'Advocate User', 'ADVOCATE', '+251900000004', 'Advocate Street 4', 'FAYDA-ADVOCATE-001', TRUE),
('litigant', 'litigant@example.com', '$2y$10$PAd29QwTpPA2YPtTeNH9i.2AlBJjMVt/68mZpMbEHFP.VJAz2SLXe', 'Litigant User', 'LITIGANT', '+251900000005', 'Litigant Street 5', 'FAYDA-LITIGANT-001', TRUE);