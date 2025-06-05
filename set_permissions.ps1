# Get the current directory where the script is located
$currentDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Define the users/groups that need access
$users = @(
    "SYSTEM",
    "Administrators",
    "Users",
    "Everyone"
)

# Set full control permissions for each user/group
foreach ($user in $users) {
    $acl = Get-Acl $currentDir
    $permission = $user, "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow"
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule $permission
    $acl.SetAccessRule($accessRule)
    Set-Acl $currentDir $acl
}

Write-Host "Permissions have been set successfully for the backend directory"
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
