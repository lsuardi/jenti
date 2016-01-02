
@echo off

REM set dest=g:\jenti
REM /D:03-26-2015 

set dest=C:\temp\jenti

xcopy /L /I /S /Y /D /EXCLUDE:xcopy_exclude.txt "C:\Program Files (x86)\Apache Software Foundation\Apache2.2\htdocs\jenti"  %dest%

@echo on
