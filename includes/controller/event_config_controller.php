<?php

/**
 * @return string
 */
function event_config_title()
{
    return _('Event config');
}

/**
 * @return array
 */
function event_config_edit_controller()
{
    global $privileges;

    if (!in_array('admin_event_config', $privileges)) {
        redirect(page_link_to('/'));
    }

    $request = request();
    $event_name = null;
    $event_welcome_msg = null;
    $buildup_start_date = null;
    $event_start_date = null;
    $event_end_date = null;
    $teardown_end_date = null;

    $event_config = EventConfig();
    if ($event_config != null) {
        $event_name = $event_config['event_name'];
        $buildup_start_date = $event_config['buildup_start_date'];
        $event_start_date = $event_config['event_start_date'];
        $event_end_date = $event_config['event_end_date'];
        $teardown_end_date = $event_config['teardown_end_date'];
        $event_welcome_msg = $event_config['event_welcome_msg'];
    }

    if ($request->has('submit')) {
        $valid = true;

        if ($request->has('event_name')) {
            $event_name = strip_request_item('event_name');
        }
        if ($event_name == '') {
            $event_name = null;
        }

        if ($request->has('event_welcome_msg')) {
            $event_welcome_msg = strip_request_item_nl('event_welcome_msg');
        }
        if ($event_welcome_msg == '') {
            $event_welcome_msg = null;
        }

        $result = check_request_date('buildup_start_date', _('Please enter buildup start date.'), true);
        $buildup_start_date = $result->getValue();
        $valid &= $result->isValid();

        $result = check_request_date('event_start_date', _('Please enter event start date.'), true);
        $event_start_date = $result->getValue();
        $valid &= $result->isValid();

        $result = check_request_date('event_end_date', _('Please enter event end date.'), true);
        $event_end_date = $result->getValue();
        $valid &= $result->isValid();

        $result = check_request_date('teardown_end_date', _('Please enter teardown end date.'), true);
        $teardown_end_date = $result->getValue();
        $valid &= $result->isValid();

        if ($buildup_start_date != null && $event_start_date != null && $buildup_start_date > $event_start_date) {
            $valid = false;
            error(_('The buildup start date has to be before the event start date.'));
        }

        if ($event_start_date != null && $event_end_date != null && $event_start_date > $event_end_date) {
            $valid = false;
            error(_('The event start date has to be before the event end date.'));
        }

        if ($event_end_date != null && $teardown_end_date != null && $event_end_date > $teardown_end_date) {
            $valid = false;
            error(_('The event end date has to be before the teardown end date.'));
        }

        if ($buildup_start_date != null && $teardown_end_date != null && $buildup_start_date > $teardown_end_date) {
            $valid = false;
            error(_('The buildup start date has to be before the teardown end date.'));
        }

        if ($valid) {
            EventConfig_update(
                $event_name,
                $buildup_start_date,
                $event_start_date,
                $event_end_date,
                $teardown_end_date,
                $event_welcome_msg
            );

            engelsystem_log(
                sprintf(
                    'Changed event config: %s, %s, %s, %s, %s, %s',
                    $event_name,
                    $event_welcome_msg,
                    date('Y-m-d', $buildup_start_date),
                    date('Y-m-d', $event_start_date),
                    date('Y-m-d', $event_end_date),
                    date('Y-m-d', $teardown_end_date)
                )
            );
            success(_('Settings saved.'));
            redirect(page_link_to('admin_event_config'));
        }
    }

    return [
        event_config_title(),
        EventConfig_edit_view(
            $event_name,
            $event_welcome_msg,
            $buildup_start_date,
            $event_start_date,
            $event_end_date,
            $teardown_end_date
        )
    ];
}
