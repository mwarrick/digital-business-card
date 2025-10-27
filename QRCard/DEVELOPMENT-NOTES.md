# Development Notes

## Remote Server Access

### SSH Connection
- **SSH Configuration**: Located in `/sharemycard-config/` (outside web root, not in git)
- **SSH Host**: 69.57.162.186:21098
- **SSH User**: sharipbf
- **SSH Key**: /Users/markwarrick/.ssh/id_rsa
- **Database**: sharipbf_sharemycard (MySQL)

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
