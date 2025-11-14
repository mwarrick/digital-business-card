# Development Notes

## Remote Server Access

### SSH Connection
- **SSH Configuration**: Located in `/sharemycard-config/.env` (outside web root, not in git)
- **SSH Host**: Configured in `sharemycard-config/.env` (SSH_HOST:SSH_PORT)
- **SSH User**: Configured in `sharemycard-config/.env` (SSH_USER)
- **SSH Key**: Configured in `sharemycard-config/.env` (SSH_KEY)
- **Database**: Configured in `sharemycard-config/.env` (DB_NAME)

### Database Access
- **Local Database**: Not available (connection fails)
- **Remote Database**: Access via SSH tunnel or web API
- **Configuration**: `/sharemycard-config/database.php`
- **Web API**: `https://sharemycard.app/api/`

### Important Notes
- Always use remote server for database operations
- Local database connection will fail
- Use SSH tunnel or web API for database access
- Configuration files are in `/sharemycard-config/` directory

## Field Mapping Issues

### Contacts API Field Mapping
- Database uses different field names than iOS app expects
- Need to check actual database schema via SSH
- Field mapping in `Contact.swift` CodingKeys may need updates

### Debugging
- Check server logs for actual field names
- Use SSH to examine database schema
- Test API responses with real data
