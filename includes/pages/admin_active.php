<?php

use Engelsystem\Database\DB;

/**
 * @return string
 */
function admin_active_title()
{
    return _('Active angels');
}

/**
 * @return string
 */
function admin_active()
{
    $tshirt_sizes = config('tshirt_sizes');
    $shift_sum_formula = config('shift_sum_formula');
    $request = request();

    $msg = '';
    $search = '';
    $forced_count = count(DB::select('SELECT `UID` FROM `User` WHERE `force_active`=1'));
    $count = $forced_count;
    $limit = '';
    $set_active = '';

    if ($request->has('search')) {
        $search = strip_request_item('search');
    }

    $show_all_shifts = $request->has('show_all_shifts');

    if ($request->has('set_active')) {
        $valid = true;

        if ($request->has('count') && preg_match('/^\d+$/', $request->input('count'))) {
            $count = strip_request_item('count');
            if ($count < $forced_count) {
                error(sprintf(
                    _('At least %s angels are forced to be active. The number has to be greater.'),
                    $forced_count
                ));
                redirect(page_link_to('admin_active'));
            }
        } else {
            $valid = false;
            $msg .= error(_('Please enter a number of angels to be marked as active.'), true);
        }

        if ($valid) {
            $limit = ' LIMIT ' . $count;
        }
        if ($request->has('ack')) {
            DB::update('UPDATE `User` SET `Aktiv` = 0 WHERE `Tshirt` = 0');
            $users = DB::select(sprintf('
                  SELECT
                      `User`.*,
                      COUNT(`ShiftEntry`.`id`) AS `shift_count`,
                      %s AS `shift_length`
                  FROM `User`
                  LEFT JOIN `ShiftEntry` ON `User`.`UID` = `ShiftEntry`.`UID`
                  LEFT JOIN `Shifts` ON `ShiftEntry`.`SID` = `Shifts`.`SID`
                  WHERE `User`.`Gekommen` = 1
                  AND `User`.`force_active`=0
                  GROUP BY `User`.`UID`
                  ORDER BY `force_active` DESC, `shift_length` DESC
                  %s
                ',
                $shift_sum_formula,
                $limit
            ));
            $user_nicks = [];
            foreach ($users as $usr) {
                DB::update('UPDATE `User` SET `Aktiv` = 1 WHERE `UID`=?', [$usr['UID']]);
                $user_nicks[] = User_Nick_render($usr);
            }
            DB::update('UPDATE `User` SET `Aktiv`=1 WHERE `force_active`=TRUE');
            engelsystem_log('These angels are active now: ' . join(', ', $user_nicks));

            $limit = '';
            $msg = success(_('Marked angels.'), true);
        } else {
            $set_active = '<a href="' . page_link_to('admin_active', ['search' => $search]) . '">&laquo; '
                . _('back')
                . '</a> | <a href="'
                . page_link_to(
                    'admin_active',
                    ['search' => $search, 'count' => $count, 'set_active' => 1, 'ack' => 1]
                ) . '">'
                . _('apply')
                . '</a>';
        }
    }

    if ($request->has('active') && preg_match('/^\d+$/', $request->input('active'))) {
        $user_id = $request->input('active');
        $user_source = User($user_id);
        if ($user_source != null) {
            DB::update('UPDATE `User` SET `Aktiv`=1 WHERE `UID`=? LIMIT 1', [$user_id]);
            engelsystem_log('User ' . User_Nick_render($user_source) . ' is active now.');
            $msg = success(_('Angel has been marked as active.'), true);
        } else {
            $msg = error(_('Angel not found.'), true);
        }
    } elseif ($request->has('not_active') && preg_match('/^\d+$/', $request->input('not_active'))) {
        $user_id = $request->input('not_active');
        $user_source = User($user_id);
        if ($user_source != null) {
            DB::update('UPDATE `User` SET `Aktiv`=0 WHERE `UID`=? LIMIT 1', [$user_id]);
            engelsystem_log('User ' . User_Nick_render($user_source) . ' is NOT active now.');
            $msg = success(_('Angel has been marked as not active.'), true);
        } else {
            $msg = error(_('Angel not found.'), true);
        }
    } elseif ($request->has('tshirt') && preg_match('/^\d+$/', $request->input('tshirt'))) {
        $user_id = $request->input('tshirt');
        $user_source = User($user_id);
        if ($user_source != null) {
            DB::update('UPDATE `User` SET `Tshirt`=1 WHERE `UID`=? LIMIT 1', [$user_id]);
            engelsystem_log('User ' . User_Nick_render($user_source) . ' has tshirt now.');
            $msg = success(_('Angel has got a t-shirt.'), true);
        } else {
            $msg = error('Angel not found.', true);
        }
    } elseif ($request->has('not_tshirt') && preg_match('/^\d+$/', $request->input('not_tshirt'))) {
        $user_id = $request->input('not_tshirt');
        $user_source = User($user_id);
        if ($user_source != null) {
            DB::update('UPDATE `User` SET `Tshirt`=0 WHERE `UID`=? LIMIT 1', [$user_id]);
            engelsystem_log('User ' . User_Nick_render($user_source) . ' has NO tshirt.');
            $msg = success(_('Angel has got no t-shirt.'), true);
        } else {
            $msg = error(_('Angel not found.'), true);
        }
    }

    $users = DB::select(sprintf('
            SELECT
                `User`.*,
                COUNT(`ShiftEntry`.`id`) AS `shift_count`,
                %s AS `shift_length`
            FROM `User` LEFT JOIN `ShiftEntry` ON `User`.`UID` = `ShiftEntry`.`UID`
            LEFT JOIN `Shifts` ON `ShiftEntry`.`SID` = `Shifts`.`SID` '
        . ($show_all_shifts ? '' : 'AND (`Shifts`.`end` < ' . time() . " OR `Shifts`.`end` IS NULL)") . '
            WHERE `User`.`Gekommen` = 1
            GROUP BY `User`.`UID`
            ORDER BY `force_active` DESC, `shift_length` DESC
            %s
        ',
        $shift_sum_formula,
        $limit
    ));
    $matched_users = [];
    if ($search == '') {
        $tokens = [];
    } else {
        $tokens = explode(' ', $search);
    }
    foreach ($users as &$usr) {
        if (count($tokens) > 0) {
            $match = false;
            foreach ($tokens as $t) {
                if (stristr($usr['Nick'], trim($t))) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
        }
        $usr['nick'] = User_Nick_render($usr);
        $usr['shirt_size'] = $tshirt_sizes[$usr['Size']];
        $usr['work_time'] = round($usr['shift_length'] / 60) . ' min (' . round($usr['shift_length'] / 3600) . ' h)';
        $usr['active'] = glyph_bool($usr['Aktiv'] == 1);
        $usr['force_active'] = glyph_bool($usr['force_active'] == 1);
        $usr['tshirt'] = glyph_bool($usr['Tshirt'] == 1);

        $actions = [];
        if ($usr['Aktiv'] == 0) {
            $parameters = [
                'active' => $usr['UID'],
                'search' => $search,
            ];
            if ($show_all_shifts) {
                $parameters['show_all_shifts'] = 1;
            }
            $actions[] = '<a href="' . page_link_to('admin_active', $parameters) . '">'
                . _('set active')
                . '</a>';
        }
        if ($usr['Aktiv'] == 1 && $usr['Tshirt'] == 0) {
            $parametersRemove = [
                'not_active' => $usr['UID'],
                'search'     => $search,
            ];
            $parametersShirt = [
                'tshirt' => $usr['UID'],
                'search' => $search,
            ];
            if ($show_all_shifts) {
                $parametersRemove['show_all_shifts'] = 1;
                $parametersShirt['show_all_shifts'] = 1;
            }
            $actions[] = '<a href="' . page_link_to('admin_active', $parametersRemove) . '">'
                . _('remove active')
                . '</a>';
            $actions[] = '<a href="' . page_link_to('admin_active', $parametersShirt) . '">'
                . _('got t-shirt')
                . '</a>';
        }
        if ($usr['Tshirt'] == 1) {
            $parameters = [
                'not_tshirt' => $usr['UID'],
                'search'     => $search,
            ];
            if ($show_all_shifts) {
                $parameters['show_all_shifts'] = 1;
            }
            $actions[] = '<a href="' . page_link_to('admin_active', $parameters) . '">'
                . _('remove t-shirt')
                . '</a>';
        }

        $usr['actions'] = join(' ', $actions);

        $matched_users[] = $usr;
    }

    $shirt_statistics = [];
    foreach (array_keys($tshirt_sizes) as $size) {
        if (!empty($size)) {
            $sc = DB::selectOne(
                'SELECT count(*) FROM `User` WHERE `Size`=? AND `Gekommen`=1',
                [$size]
            );
            $sc = array_shift($sc);

            $gc = DB::selectOne(
                'SELECT count(*) FROM `User` WHERE `Size`=? AND `Tshirt`=1',
                [$size]
            );
            $gc = array_shift($gc);

            $shirt_statistics[] = [
                'size'   => $size,
                'needed' => (int)$sc,
                'given'  => (int)$gc
            ];
        }
    }

    $shirtCount = DB::selectOne('SELECT count(*) FROM `User` WHERE `Tshirt`=1');
    $shirtCount = array_shift($shirtCount);

    $shirt_statistics[] = [
        'size'   => '<b>' . _('Sum') . '</b>',
        'needed' => '<b>' . User_arrived_count() . '</b>',
        'given'  => '<b>' . (int)$shirtCount . '</b>'
    ];

    return page_with_title(admin_active_title(), [
        form([
            form_text('search', _('Search angel:'), $search),
            form_checkbox('show_all_shifts', _('Show all shifts'), $show_all_shifts),
            form_submit('submit', _('Search'))
        ], page_link_to('admin_active')),
        $set_active == '' ? form([
            form_text('count', _('How much angels should be active?'), $count),
            form_submit('set_active', _('Preview'))
        ]) : $set_active,
        $msg . msg(),
        table([
            'nick'         => _('Nickname'),
            'shirt_size'   => _('Size'),
            'shift_count'  => _('Shifts'),
            'work_time'    => _('Length'),
            'active'       => _('Active?'),
            'force_active' => _('Forced'),
            'tshirt'       => _('T-shirt?'),
            'actions'      => ''
        ], $matched_users),
        '<h2>' . _('Shirt statistics') . '</h2>',
        table([
            'size'   => _('Size'),
            'needed' => _('Needed shirts'),
            'given'  => _('Given shirts')
        ], $shirt_statistics)
    ]);
}
