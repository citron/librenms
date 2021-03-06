<?php

use LibreNMS\Config;

// IPMI - We can discover this on poll!
if ($ipmi['host'] = get_dev_attrib($device, 'ipmi_hostname')) {
    echo 'IPMI : ';

    $ipmi['user']     = get_dev_attrib($device, 'ipmi_username');
    $ipmi['password'] = get_dev_attrib($device, 'ipmi_password');

    if (Config::get('own_hostname') != $device['hostname'] || $ipmi['host'] != 'localhost') {
        $remote = " -H ".$ipmi['host']." -U '".$ipmi['user']."' -P '".$ipmi['password']."' -L USER";
    }

    foreach (Config::get('ipmi.type', array()) as $ipmi_type) {
        $results = external_exec(Config::get('ipmitool')." -I $ipmi_type".$remote.' sensor 2>/dev/null|sort');
        if ($results != '') {
            set_dev_attrib($device, 'ipmi_type', $ipmi_type);
            echo "$ipmi_type ";
            break;
        }
    }

    $index = 0;

    foreach (explode("\n", $results) as $sensor) {
        // BB +1.1V IOH     | 1.089      | Volts      | ok    | na        | 1.027     | 1.054     | 1.146     | 1.177     | na
        $values = array_map('trim', explode('|', $sensor));
        list($desc,$current,$unit,$state,$low_nonrecoverable,$low_limit,$low_warn,$high_warn,$high_limit,$high_nonrecoverable) = $values;

        $index++;
        if ($current != 'na' && Config::has("ipmi_unit.$unit")) {
            discover_sensor(
                $valid['sensor'],
                Config::get("ipmi_unit.$unit"),
                $device,
                $desc,
                $index,
                'ipmi',
                $desc,
                '1',
                '1',
                $low_limit == 'na' ? null : $low_limit,
                $low_warn == 'na' ? null : $low_warn,
                $high_warn == 'na' ? null : $high_warn,
                $high_limit == 'na' ? null : $high_limit,
                $current,
                'ipmi'
            );
        }
    }

    echo "\n";
}

check_valid_sensors($device, 'voltage', $valid['sensor'], 'ipmi');
check_valid_sensors($device, 'temperature', $valid['sensor'], 'ipmi');
check_valid_sensors($device, 'fanspeed', $valid['sensor'], 'ipmi');
check_valid_sensors($device, 'power', $valid['sensor'], 'ipmi');
