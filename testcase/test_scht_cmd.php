<?php
// tasks/FirstTasks.php
    $cmd = "schtasks.exe /CREATE /TN \"RUN TEST SCHT\" /TR \"C:\File Mobile\Demo\Service-Demo-Test\\testcase\\test_scht_cmd.php\" /SC once /SD 2020/01/25 /ST 23:50 /ru Administrators /rp @Gensoft2018 /rl HIGHEST";

    pclose(popen("start /B ". $cmd, "r")); // OR exec($cmd);
	echo $cmd;
/*
    $cmd ="schtasks.exe /Change /TN \"Action Item Reminder\" /RU System";
    if (isset ($activate))
    {
        pclose(popen("start /B ". $cmd." /Enable", "r")); // OR exec($cmd);
    }
    else
    {
        pclose(popen("start /B ". $cmd." /Disable", "r")); // OR exec($cmd);
    }
	echo $cmd;*/