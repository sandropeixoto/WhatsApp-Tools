CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instance_id TEXT NOT NULL,
    message_id TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL,
    from_me INTEGER DEFAULT 0,
    push_name TEXT,
    content TEXT,
    message_type TEXT,
    timestamp INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
