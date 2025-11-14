# PowerShell script to deploy authentication files to production server
# Uses SSH password from sharemycard-config/sshkeyinfo.txt

# Server connection details should be read from sharemycard-config/.env
# For PowerShell, you can source the .env file or set these variables manually
$SERVER = "$env:SSH_USER@$env:SSH_HOST"  # e.g., "your_ssh_user@your.server.ip"
$PORT = "$env:SSH_PORT"  # e.g., "your_ssh_port"
$REMOTE_PATH = "/home/$env:SSH_USER/public_html"  # Adjust based on your server setup
$LOCAL_PATH = "web"
$SSH_PASSWORD = "M8*gFEWGO6lL790b"

Write-Host "🚀 Deploying authentication files to production server..." -ForegroundColor Green
Write-Host ""

# Function to deploy a file using sshpass equivalent
function Deploy-File {
    param(
        [string]$LocalFile,
        [string]$RemotePath
    )
    
    $fileName = Split-Path $LocalFile -Leaf
    Write-Host "📤 Uploading $fileName..." -ForegroundColor Yellow
    
    # Use plink (PuTTY) if available, otherwise use scp with expect-like behavior
    # For Windows, we'll use a simple approach: try scp and let user enter password
    # Or use ssh with password authentication
    
    # Try using ssh with password via here-string or expect-like tool
    # Since Windows doesn't have expect, we'll use a workaround
    
    # Method 1: Use plink.exe (PuTTY) if installed
    $plinkPath = Get-Command plink -ErrorAction SilentlyContinue
    if ($plinkPath) {
        Write-Host "Using PuTTY plink..." -ForegroundColor Cyan
        $fullLocalPath = (Resolve-Path $LocalFile).Path
        $remoteFullPath = "${SERVER}:${RemotePath}"
        
        # Create a temporary script file for plink
        $tempScript = [System.IO.Path]::GetTempFileName()
        "y`n" | Out-File -FilePath $tempScript -Encoding ASCII -NoNewline
        
        echo y | & plink -P $PORT -pw $SSH_PASSWORD $SERVER "mkdir -p $(Split-Path $RemotePath -Parent)"
        echo y | & plink -P $PORT -pw $SSH_PASSWORD -scp $fullLocalPath $remoteFullPath
        
        Remove-Item $tempScript -ErrorAction SilentlyContinue
    } else {
        # Method 2: Use scp with password (requires manual entry or sshpass equivalent)
        Write-Host "Note: You may be prompted for SSH password: $SSH_PASSWORD" -ForegroundColor Cyan
        $fullLocalPath = (Resolve-Path $LocalFile).Path
        $remoteFullPath = "${SERVER}:${RemotePath}"
        
        # Try using the password with scp
        # Note: scp on Windows doesn't support password via command line for security
        # So we'll provide instructions
        Write-Host "Please enter password when prompted: $SSH_PASSWORD" -ForegroundColor Yellow
        scp -P $PORT $fullLocalPath $remoteFullPath
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ $fileName uploaded successfully" -ForegroundColor Green
        } else {
            Write-Host "❌ Failed to upload $fileName (exit code: $LASTEXITCODE)" -ForegroundColor Red
        }
    }
}

# Deploy files
Deploy-File "$LOCAL_PATH\api\auth\login.php" "$REMOTE_PATH/api/auth/login.php"
Deploy-File "$LOCAL_PATH\api\auth\resend-verification.php" "$REMOTE_PATH/api/auth/resend-verification.php"
Deploy-File "$LOCAL_PATH\api\test\check-email.php" "$REMOTE_PATH/api/test/check-email.php"

Write-Host ""
Write-Host "✅ Deployment complete!" -ForegroundColor Green
Write-Host "You can now test the login flow in the Android app." -ForegroundColor Cyan
