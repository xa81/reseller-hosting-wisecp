<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG           = $module->lang;
    $product        = isset($product) && $product ? $product : array();
    $module_data    = isset($product["module_data"]) ? Utility::jdecode($product["module_data"], true) : array();
    $create_account = isset($module_data["create_account"]) ? $module_data["create_account"] : $module_data;
    $selected       = isset($create_account["plan"]) ? $create_account["plan"] : '';

    $plans = $module->getPlans();
?>
<div class="formcon">
    <div class="yuzde30"><?php echo $LANG["detected-panel"]; ?></div>
    <div class="yuzde70">
        <?php
            if ($plans === false) {
                echo '<span class="error">' . htmlspecialchars((string) $module->error, ENT_QUOTES, 'UTF-8') . '</span>';
            } else {
                echo $module->panel() === 'plesk' ? $LANG["panel-plesk"] : $LANG["panel-cpanel"];
            }
        ?>
    </div>
</div>

<div class="formcon" id="plans_wrap">
    <div class="yuzde30"><?php echo $LANG["select-plan"]; ?></div>
    <div class="yuzde70">
        <?php if ($plans === false || !$plans): ?>
            <em><?php echo $LANG["no-plans"]; ?></em>
        <?php else: ?>
            <select name="module_data[plan]" id="module_data_plan">
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo htmlspecialchars($plan["name"], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $plan["name"] === $selected ? ' selected' : ''; ?>>
                        <?php echo htmlspecialchars($plan["name"], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</div>
