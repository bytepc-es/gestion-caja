[Setup]
AppName=Gestion de Caja
AppVersion=1.2.0
AppPublisher=Bytespc.es
AppCopyright=Copyright (C) 2026 Bytespc.es. Todos los derechos reservados.

; Usamos exactamente el mismo nombre que busca el archivo db.php (sin espacio)
DefaultDirName={userdocs}\GestionCaja
DefaultGroupName=Gestion de Caja
UsePreviousAppDir=no
UsePreviousGroup=no

OutputDir=Output
OutputBaseFilename=Instalador_Gestion_Caja_v1.2
Compression=lzma
SolidCompression=yes

; Permisos de administrador solo para copiar los archivos
PrivilegesRequired=admin

VersionInfoCompany=Bytespc.es
VersionInfoDescription=Instalador de Gestion de Caja v1.2
VersionInfoCopyright=Copyright (C) 2026 Bytespc.es

SetupIconFile=icon.ico
UninstallDisplayIcon={app}\icon.ico

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
; 1. Copiamos todo ignorando los ejecutables para no duplicar
Source: "C:\Users\HP\Downloads\phpdesktop-chrome-130.1-php-8.3\*"; DestDir: "{app}"; Excludes: "phpdesktop-chrome.exe, GestionCaja.exe, GestionCaja.exe.exe"; Flags: ignoreversion recursesubdirs createallsubdirs

; 2. Copiamos el ejecutable original y lo forzamos a llamarse GestionCaja.exe
Source: "C:\Users\HP\Downloads\phpdesktop-chrome-130.1-php-8.3\phpdesktop-chrome.exe"; DestDir: "{app}"; DestName: "GestionCaja.exe"; Flags: ignoreversion

[Icons]
; Accesos directos apuntando al ejecutable correcto
Name: "{group}\Gestion de Caja"; Filename: "{app}\GestionCaja.exe"; IconFilename: "{app}\icon.ico"
Name: "{autodesktop}\Gestion de Caja"; Filename: "{app}\GestionCaja.exe"; IconFilename: "{app}\icon.ico"; Tasks: desktopicon