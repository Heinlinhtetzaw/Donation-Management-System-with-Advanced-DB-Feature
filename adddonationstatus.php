<?php
require_once 'auth_check.php';
set_flash('error', 'The legacy status screen has been retired. Use the Donation Ledger to update a record with its required history.');
redirect_to('donation_ledger.php');
