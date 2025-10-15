# API Documentation

## Base URL
```
Production: https://your-domain.com/api
Development: http://localhost/digital-business-card/api
```

## Authentication
All API endpoints (except registration and login) require authentication via Bearer token.

```
Authorization: Bearer {token}
```

## Endpoints

### Authentication

#### Register User
```http
POST /auth/register
Content-Type: application/json

{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Verification code sent to email",
    "user_id": 123
}
```

#### Verify Registration
```http
POST /auth/verify
Content-Type: application/json

{
    "user_id": 123,
    "verification_code": "123456"
}
```

**Response:**
```json
{
    "success": true,
    "token": "jwt_token_here",
    "user": {
        "id": 123,
        "email": "user@example.com",
        "created_at": "2025-10-10T12:00:00Z"
    }
}
```

#### Login
```http
POST /auth/login
Content-Type: application/json

{
    "email": "user@example.com"
}
```

### Business Cards

#### Get User's Cards
```http
GET /cards
Authorization: Bearer {token}
```

#### Create Business Card
```http
POST /cards
Authorization: Bearer {token}
Content-Type: application/json

{
    "first_name": "John",
    "last_name": "Doe",
    "phone_number": "+1234567890",
    "additional_emails": [
        {"type": "work", "email": "john@company.com"}
    ],
    "additional_phones": [
        {"type": "mobile", "number": "+1987654321"}
    ],
    "websites": [
        {"name": "Portfolio", "url": "https://johndoe.com"}
    ],
    "company_name": "Acme Corp",
    "job_title": "Developer",
    "bio": "Passionate developer...",
    "address": {
        "street": "123 Main St",
        "city": "Anytown",
        "state": "CA",
        "zip": "12345",
        "country": "USA"
    }
}
```

#### Update Business Card
```http
PUT /cards/{card_id}
Authorization: Bearer {token}
Content-Type: application/json
```

#### Delete Business Card
```http
DELETE /cards/{card_id}
Authorization: Bearer {token}
```

### Media Upload

#### Upload Image
```http
POST /upload/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "image": [file],
    "type": "profile|logo|cover"
}
```

## Error Responses

```json
{
    "success": false,
    "error": "error_code",
    "message": "Human readable error message"
}
```

### Common Error Codes
- `INVALID_TOKEN` - Authentication token is invalid or expired
- `VALIDATION_ERROR` - Request data validation failed
- `NOT_FOUND` - Requested resource not found
- `RATE_LIMIT` - Too many requests