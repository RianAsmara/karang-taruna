<?php

namespace App\Enums;

/**
 * mobile-screens.md § 27 permission note: "Voting ini hanya untuk
 * pengurus." implies some votes restrict eligibility to management
 * roles, others are open to every member.
 */
enum VoteEligibleScope: string
{
    case All = 'ALL';
    case Pengurus = 'PENGURUS';
}
