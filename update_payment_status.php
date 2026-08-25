<?php
require_once 'auth_check.php';
require_once 'csrf.php';

require_post_request('donation_ledger.php');
require_valid_csrf('donation_ledger.php');
set_flash('error', 'Legacy status updates are disabled. Open the donation from the Donation Ledger to record a traceable status change.');
redirect_to('donation_ledger.php');
