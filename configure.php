<?php
// This should be used to configure new servers and reconfigure pre-existing ones.
// 1) Give space for required server configurations.
// 2) Give configuration options on tabs for command line configs.
// 3) Give configuration options on tabs for advacned configs.
include_once('./vars.php');
?>
<html>
<head>
</head>
<body>
<div id="basics">
<?php
$n = "\n";
foreach ($options as $option => $info) {
        if ($info['type'] == 'bool') {
                $default = '';
                if (isset($info['default'])) { if ($info['default'] == 'true') { $default = ' CHECKED=CHECKED'; } }
                print '<div class="option"><input type="checkbox" name="'.$option.'"'.$default.'>';
                print '<label for="'.$option.'">'.$option.'</label></div>'.$n;
        } else {
                $default = '';
                if (isset($info['default'])) { $default = $info['default']; }
                print '<div class="option"><input type="text" name="'.$option.'" value="'.$default.'">'.$option.'</div>'.$n;
        }
}
?>
</div>
</body>
</html>
