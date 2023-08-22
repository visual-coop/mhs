@echo off
::set /p Build=<test2.txt
::%Build%
::for /f "delims=" %%x in (test.txt) do %%x
::%Build%
::schtasks.exe /CREATE /TN "RUN TEST SCHT" /TR "C:\File Mobile\Demo\Service-Demo-Test\testcase\test_scht_cmd.php" /SC once /SD 2020/01/25 /ST 23:50 /ru Administrators /rp @Gensoft2018 /rl HIGHEST

SET mypath=%~dp0
::echo %mypath:~0,-9%
echo %computername%
schtasks.exe /CREATE /SC DAILY /TN "RUN TEST SCHT" /TR "%mypath:~0,-1%\test_scht_cmd.php"  /ri 1 /SD 01/25/2021 /ST 23:50 /ru Administrators /rp @Icoop2012 /rl HIGHEST /it
pause