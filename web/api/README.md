# ShareMyCard REST API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints except `/auth/register`, `/auth/login`, and `/auth/verify` require authentication.

**Authentication uses JWT (JSON Web Tokens):**
```
Authorization: Bearer {jwt_token}
```

After successful verification, you'll receive a JWT token that is valid for 30 days. Include this token in the Authorization header for all authenticated requests.

## Rate Limiting

All endpoints are rate-limited to prevent abuse:

- **Register**: 5 requests per hour per IP
- **Login**: 10 requests per hour per IP
- **Verify**: 10 requests per hour per IP
- **Business Cards**: 100 requests per hour per user
- **Media Upload**: 20 requests per hour per user
- **Media Delete**: 50 requests per hour per user
- **QR Code**: 100 requests per hour per user

Rate limit information is returned in response headers:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Unix timestamp when the rate limit resets
- `Retry-After`: Seconds to wait before retrying (only when limit exceeded)

## Endpoints

### Authentication

#### Register User
```http
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "message": "Registration successful. Verification email sent."
  }
}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Verification code sent",
  "data": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "is_admin": false,
    "message": "Verification code sent to your email. Please enter it to complete login."
  }
}
```

#### Verify Code
```http
POST /api/auth/verify
Content-Type: application/json

{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Verification successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "is_admin": false,
    "is_active": true,
    "verification_type": "login",
    "token_expires_in": 2592000,
    "message": "Login successful!"
  }
}
```

### Business Cards

#### List All Cards
```http
GET /api/cards/
Authorization: Bearer {user_id}
```

**Response:**
```json
{
  "success": true,
  "message": "Cards retrieved successfully",
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "user_id": "550e8400-e29b-41d4-a716-446655440000",
      "first_name": "Mark",
      "last_name": "Warrick",
      "phone_number": "+1 (555) 123-4567",
      "company_name": "ShareMyCard Development",
      "job_title": "iOS Developer",
      "bio": "Passionate about creating innovative digital solutions.",
      "emails": [
        {
          "id": "...",
          "email": "mark@warrick.net",
          "type": "work",
          "label": "Primary"
        }
      ],
      "phones": [
        {
          "id": "...",
          "phone_number": "+1 (555) 987-6543",
          "type": "mobile",
          "label": "Mobile"
        }
      ],
      "websites": [],
      "address": null,
      "is_active": 1,
      "created_at": "2025-10-10 17:05:01",
      "updated_at": "2025-10-10 17:05:01"
    }
  ]
}
```

#### Get Single Card
```http
GET /api/cards/?id={card_id}
Authorization: Bearer {user_id}
```

#### Create Card
```http
POST /api/cards/
Authorization: Bearer {user_id}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "phone_number": "+1 (555) 123-4567",
  "company_name": "Acme Corp",
  "job_title": "Software Engineer",
  "bio": "Full-stack developer with 10 years experience.",
  "emails": [
    {
      "email": "john@acme.com",
      "type": "work",
      "label": "Work Email"
    },
    {
      "email": "john.personal@example.com",
      "type": "personal",
      "label": "Personal"
    }
  ],
  "phones": [
    {
      "phone_number": "+1 (555) 987-6543",
      "type": "mobile",
      "label": "Mobile"
    }
  ],
  "websites": [
    {
      "url": "https://johndoe.com",
      "name": "Portfolio",
      "description": "My personal website"
    }
  ],
  "address": {
    "street": "123 Main St",
    "city": "San Francisco",
    "state": "CA",
    "postal_code": "94102",
    "country": "USA"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Card created successfully",
  "data": {
    "id": "...",
    "user_id": "...",
    "first_name": "John",
    "last_name": "Doe",
    ...
  }
}
```

#### Update Card
```http
PUT /api/cards/?id={card_id}
Authorization: Bearer {user_id}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "phone_number": "+1 (555) 123-4567",
  ...
}
```

#### Delete Card
```http
DELETE /api/cards/?id={card_id}
Authorization: Bearer {user_id}
```

**Response:**
```json
{
  "success": true,
  "message": "Card deleted successfully",
  "data": []
}
```

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {}
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `409` - Conflict
- `500` - Internal Server Error

## Testing

Use curl or Postman to test the API:

### Register a new user
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

### Get all cards
```bash
curl http://localhost:8000/api/cards/ \
  -H "Authorization: Bearer 550e8400-e29b-41d4-a716-446655440000"
```

### Create a card
```bash
curl -X POST http://localhost:8000/api/cards/ \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "phone_number": "+1 (555) 123-4567",
    "company_name": "Acme Corp",
    "job_title": "Software Engineer"
  }'
```

## Media Management

### Upload Media
Upload profile photos, company logos, or cover graphics for business cards.

**Endpoint:**
```http
POST /api/media/upload
Content-Type: multipart/form-data
Authorization: Bearer {jwt_token}
```

**Parameters:**
- `business_card_id` (string, required) - The business card ID
- `media_type` (string, required) - Type of media: `profile_photo`, `company_logo`, or `cover_graphic`
- `file` (file, required) - Image file (JPEG, PNG, GIF, WebP, max 5MB)

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "filename": "550e8400-e29b-41d4-a716-446655440001.jpg",
    "url": "/api/media/view?file=550e8400-e29b-41d4-a716-446655440001.jpg",
    "media_type": "profile_photo",
    "business_card_id": "550e8400-e29b-41d4-a716-446655440000",
    "size": 245678,
    "mime_type": "image/jpeg"
  }
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/media/upload \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -F "business_card_id=550e8400-e29b-41d4-a716-446655440000" \
  -F "media_type=profile_photo" \
  -F "file=@/path/to/photo.jpg"
```

### View Media
Retrieve an uploaded media file.

**Endpoint:**
```http
GET /api/media/view?file={filename}
```

**Response:** Returns the image file directly

**Example:**
```
http://localhost:8000/api/media/view?file=550e8400-e29b-41d4-a716-446655440001.jpg
```

### Delete Media
Remove media from a business card.

**Endpoint:**
```http
DELETE /api/media/delete?business_card_id={id}&media_type={type}
Authorization: Bearer {jwt_token}
```

**Parameters:**
- `business_card_id` (string, required) - The business card ID
- `media_type` (string, required) - Type of media to delete: `profile_photo`, `company_logo`, or `cover_graphic`

**Response:**
```json
{
  "success": true,
  "message": "Media file deleted successfully",
  "data": {
    "business_card_id": "550e8400-e29b-41d4-a716-446655440000",
    "media_type": "profile_photo",
    "deleted_file": "550e8400-e29b-41d4-a716-446655440001.jpg"
  }
}
```

## QR Code Generation

### Generate QR Code
Generate a QR code with full vCard data for a business card.

**Endpoint:**
```http
GET /api/cards/qrcode?id={card_id}&format={format}&size={size}
Authorization: Bearer {jwt_token}
```

**Parameters:**
- `id` (string, required) - Business card ID
- `format` (string, optional) - Response format: `json` (default) or `image`
- `size` (integer, optional) - QR code size in pixels (default: 300, max: 1000)

**Response (JSON format):**
```json
{
  "success": true,
  "message": "QR code generated successfully",
  "data": {
    "business_card_id": "550e8400-e29b-41d4-a716-446655440000",
    "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=...",
    "vcard_data": "BEGIN:VCARD\r\nVERSION:3.0\r\n...",
    "size": 300,
    "format": "png"
  }
}
```

**Response (image format):** Returns PNG image directly

**cURL Example:**
```bash
# Get JSON with QR code URL
curl http://localhost:8000/api/cards/qrcode?id=550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# Get PNG image directly
curl http://localhost:8000/api/cards/qrcode?id=550e8400-e29b-41d4-a716-446655440000&format=image \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  --output qrcode.png
```

### vCard Data Format
The QR code contains complete contact information in vCard 3.0 format:
- Name (first, last)
- Organization and job title
- Phone numbers (primary + additional)
- Email addresses
- Website URLs
- Physical address
- Bio/notes

