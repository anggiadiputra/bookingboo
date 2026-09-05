# RPD (Relational Process Diagram)

RPD di sini berarti **Relational Process Diagram**, yaitu hubungan antaraktor, proses, data, dan hasil proses.

```mermaid
flowchart LR
    U1["Customer"] --> P1["Registrasi dan isi kebutuhan"]
    U2["Caregiver"] --> P2["Registrasi dan verifikasi"]
    A["Admin"] --> P2
    P1 --> D1[("Data pengguna")]
    P2 --> D2[("Profil caregiver")]
    U1 --> P3["Pencarian caregiver"]
    D2 --> P3
    P3 --> P4["Pengajuan booking"]
    P4 --> U2
    U2 --> P5["Terima atau tolak booking"]
    P5 --> D3[("Data booking")]
    D3 --> P6["Invoice dan pembayaran"]
    P6 <--> PG["Payment Gateway"]
    P6 --> P7["Konfirmasi booking"]
    P7 --> P8["Check-in, layanan, check-out"]
    U2 --> P8
    P8 --> P9["Hitung biaya dan komisi"]
    P9 --> D4[("Data pembayaran")]
    P8 --> P10["Review dua arah"]
    U1 --> P10
    U2 --> P10
    P10 --> D5[("Data review")]
    A --> P11["Moderasi dan penyelesaian komplain"]
    D5 --> P11
```

## ERD (Entity Relationship Diagram)

RPD menggambarkan alur proses. Untuk desain database, ERD berikut melengkapi hubungan antar entitas.

```mermaid
erDiagram
    USER ||--o| CAREGIVER : "adalah"
    USER ||--o| CUSTOMER : "adalah"
    CUSTOMER ||--o{ BOOKING : "membuat"
    CAREGIVER ||--o{ BOOKING : "menerima"
    CAREGIVER ||--o{ SCHEDULE : "memiliki"
    BOOKING ||--o{ PAYMENT : "menghasilkan"
    BOOKING ||--o{ REVIEW : "mendapat"
    BOOKING ||--o{ COMPLAINT : "dapat memiliki"
    USER ||--o{ COMPLAINT : "mengajukan"
    USER ||--o{ NOTIFICATION : "menerima"
    BOOKING ||--o{ MESSAGE : "memiliki"
    USER ||--o{ MESSAGE : "mengirim"

    USER {
        int id PK
        string name
        string email
        string phone
        string role
        string password_hash
        string status
    }
    CUSTOMER {
        int id PK
        int user_id FK
        string address
        string emergency_contact
    }
    CAREGIVER {
        int id PK
        int user_id FK
        string skills
        string service_area
        decimal hourly_rate
        float rating
        string verification_status
    }
    SCHEDULE {
        int id PK
        int caregiver_id FK
        datetime start_time
        datetime end_time
        string status
    }
    BOOKING {
        int id PK
        int customer_id FK
        int caregiver_id FK
        datetime start_time
        datetime end_time
        string location
        string needs
        string status
    }
    PAYMENT {
        int id PK
        int booking_id FK
        decimal amount
        decimal platform_fee
        decimal commission
        string status
        datetime paid_at
    }
    REVIEW {
        int id PK
        int booking_id FK
        int reviewer_id FK
        int reviewee_id FK
        int rating
        string comment
        string visibility
    }
    COMPLAINT {
        int id PK
        int booking_id FK
        int reporter_id FK
        string reason
        string status
    }
    NOTIFICATION {
        int id PK
        int user_id FK
        string type
        string content
        datetime sent_at
        string status
    }
    MESSAGE {
        int id PK
        int booking_id FK
        int sender_id FK
        string content
        datetime sent_at
        string read_status
    }
```
