[Setup]
AppName=Gestion de Caja
AppVersion=1.0
; AQUÍ APARECE TU NOMBRE COMO CREADOR
AppPublisher=Bytespc.es
AppCopyright=Copyright (C) 2026 Bytespc.es. Todos los derechos reservados.
DefaultDirName={autopf}\Gestion de Caja
DefaultGroupName=Gestion de Caja
OutputDir=Output
OutputBaseFilename=Instalador_Gestion_Caja
Compression=lzma
SolidCompression=yes
PrivilegesRequired=admin

; Propiedades del archivo .exe
VersionInfoCompany=Bytespc.es
VersionInfoDescription=Instalador de Gestion de Caja
VersionInfoCopyright=Copyright (C) 2026 Bytespc.es

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
; RUTA EXACTA CONFIGURADA CON TU USUARIO "HP"
Source: "C:\Users\HP\Downloads\phpdesktop-chrome-130.1-php-8.3\phpdesktop-chrome-130.1-php-8.3\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\Gestion de Caja"; Filename: "{app}\phpdesktop-chrome.exe"
Name: "{autodesktop}\Gestion de Caja"; Filename: "{app}\phpdesktop-chrome.exe"; Tasks: desktopicon

[Run]
Filename: "{app}\phpdesktop-chrome.exe"; Description: "{cm:LaunchProgram,Gestion de Caja}"; Flags: nowait postinstall skipifsilent