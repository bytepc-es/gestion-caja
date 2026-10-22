[Setup]
AppName=Gestion de Caja
AppVersion=1.2.0
AppPublisher=Bytespc.es
AppCopyright=Copyright (C) 2026 Bytespc.es. Todos los derechos reservados.
DefaultDirName={autopf}\Gestion de Caja
DefaultGroupName=Gestion de Caja
OutputDir=Output
OutputBaseFilename=Instalador_Gestion_Caja_v1.2
Compression=lzma
SolidCompression=yes
PrivilegesRequired=admin
VersionInfoCompany=Bytespc.es
VersionInfoDescription=Instalador de Gestion de Caja v1.2
VersionInfoCopyright=Copyright (C) 2026 Bytespc.es

; ICONO DEL INSTALADOR
SetupIconFile=icon.ico
UninstallDisplayIcon={app}\icon.ico

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
; RUTA CORREGIDA SEGÚN TU CAPTURA
Source: "C:\Users\HP\Downloads\phpdesktop-chrome-130.1-php-8.3\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
; ACCESOS DIRECTOS USANDO EL NUEVO NOMBRE GestionCaja.exe
Name: "{group}\Gestion de Caja"; Filename: "{app}\GestionCaja.exe"; IconFilename: "{app}\icon.ico"
Name: "{autodesktop}\Gestion de Caja"; Filename: "{app}\GestionCaja.exe"; IconFilename: "{app}\icon.ico"; Tasks: desktopicon

[Run]
; EJECUTAR EL NUEVO NOMBRE
Filename: "{app}\GestionCaja.exe"; Description: "{cm:LaunchProgram,Gestion de Caja}"; Flags: nowait postinstall skipifsilent