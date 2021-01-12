<?php
/**
 * File: rewrites.php.
 *
 * @author Justin Greer <justin@justin-greer.com
 */
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class WPOSSO_Rewrites.
 */
class WPOSSO_Rewrites
{
    private $mmedia_user;

    public function __construct(M_WPOSSO_User $mmedia_user)
    {
        $this->mmedia_user = $mmedia_user;
    }

    public function create_rewrite_rules($rules)
    {
        global $wp_rewrite;
        $newRule = ['auth/(.+)' => 'index.php?auth=' . $wp_rewrite->preg_index(1)];
        $newRules = $newRule + $rules;

        return $newRules;
    }

    public function add_query_vars($qvars)
    {
        $qvars[] = 'auth';

        return $qvars;
    }

    public function flush_rewrite_rules()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    public function template_redirect_intercept()
    {
        global $wp_query;
        if ($wp_query->get('auth') && $wp_query->get('auth') == 'sso') {
            require_once dirname(dirname(__FILE__)) . '/includes/callback.php';
            exit;
        }
    }
}

$WPOSSO_Rewrites = new WPOSSO_Rewrites($mmedia_user);
add_filter('rewrite_rules_array', [$WPOSSO_Rewrites, 'create_rewrite_rules']);
add_filter('query_vars', [$WPOSSO_Rewrites, 'add_query_vars']);
add_filter('wp_loaded', [$WPOSSO_Rewrites, 'flush_rewrite_rules']);
add_action('template_redirect', [$WPOSSO_Rewrites, 'template_redirect_intercept']);
