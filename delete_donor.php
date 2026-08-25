<?php
require_once 'auth_check.php';
require_once 'csrf.php';

require_post_request('donor.php');
require_valid_csrf('donor.php');
set_flash('error', 'Donation records are financial history and cannot be deleted. Use the Donation Ledger to cancel or reject a record with an audit note.');
redirect_to('donor.php');
