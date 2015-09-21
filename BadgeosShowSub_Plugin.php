<?php


include_once('BadgeosShowSub_LifeCycle.php');

class BadgeosShowSub_Plugin extends BadgeosShowSub_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
            'AmAwesome' => array(__('I like this awesome plugin', 'my-awesome-plugin'), 'false', 'true'),
            'CanDoSomething' => array(__('Which user role can do something', 'my-awesome-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'BadgeOS Show Submission Add-on';
    }

    protected function getMainPluginFileName() {
        return 'badgeos-show-submission-add-on.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37
		add_filter('badgeos_render_achievement', array($this, 'addLinkToSubmission'), 10, 3);
		#add_filter('badgeos_render_submission', array($this, 'addLinkToSubmission'), 10, 3);
		add_filter('bp_core_signup_send_validation_email_message', array($this, 'ajouterSalutationEtSignature'), 10, 3);
		add_filter('the_content', array($this, 'removeBadgeThumbnail'), 11);
		add_filter('badgeos_get_submission_attachments', array($this, 'swapWordAttachmentProof'), 11);
        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }


    public function removeBadgeThumbnail($content) {
    	$postType = get_post_type();

    	if($postType == 'badges') {
	    	$content = preg_replace('#<div class="alignleft badgeos-item-image">(.*?)</div>#', '', $content);
    	}

    	return $content;
    }


    public function swapWordAttachmentProof($content) {


    	$content = str_replace('Submitted Attachments:', 'Submitted proofs:', $content);
    	$content = str_replace('Pièces-Jointes Soumises :', ' Preuves soumises:', $content);

    	return $content;
    }


    public function ajouterSalutationEtSignature($message,    $user_id, $activate_url) {
		$diplayName = '';

    	if(!empty($user_id)) {
    		$diplayName = bp_core_get_user_displayname($user_id);
    	}

    	$signature = 'Administrateur Web'."\n".
			'CADRE 21 - Centre d’animation, de développement et de '.
			'recherche en éducation pour le 21e siècle'."\n".
			'http://www.cadre21.org';

    	$message = "Bonjour $diplayName,\n\n" . $message . "\n\n" . $signature;

    	return $message;
    }



    public function addLinkToSubmission($output, $achivementID, $mode) {
		$displayedID = bp_displayed_user_id();

		if(empty($displayedID)) {
			return $output;
		}

		/*
		 * Now we take care of removing the thumbnail as requested by client
		 */
		$doc = new DOMDocument();
		#self::setErrorHandler();
		$doc->loadHTML (mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8'));
		#self::setErrorHandler(TRUE);
		$xpath = new \DOMXPath ( $doc );

		/*
		 * Du parent div ID = badgeos-achievements-container,
		 * les enfants qui ont la classe badgeos-item-image
		 * From http://stackoverflow.com/questions/11686287/php-xml-removing-element-and-all-children-according-to-node-value
		*/
		$q = '//div[@class="badgeos-item-image"]';
		$thumbnailDiv = $xpath->query($q);
		$parentNode = $thumbnailDiv->item(0)->parentNode;
		$parentNode->removeChild($thumbnailDiv->item(0));

		$q = '//div[@class="badgeos-item-description"]';
		$descDiv = $xpath->query($q)->item(0);
		$descDiv->setAttribute("style", "width:100%");

		$q = '//h2[@class="badgeos-item-title"]';
		$descDiv = $xpath->query($q)->item(0);
		$descDiv->setAttribute("style", "font-size:140%");

		$strReturn = $doc->saveHTML();
		#$strReturn = $output;

		/*
		 * According to badgeos_parse_feedback_args
		 * 'status'=> 'auto-approved' shows both approved & auto-approved
		 */
		$args = array(
				'author'			=> $displayedID,
				'achievement_id'	=> $achivementID,
				'post_type'    		=> 'submission',
				'show_attachments'	=> true,
				'show_comments'		=> true,
				'status'			=> 'auto-approved',
				'numberposts'		=> 1,
				'suppress_filters'	=> false,
		);

		$args = badgeos_parse_feedback_args($args);


		$wpq 	= new WP_Query;
		$posts	= $wpq->query($args);
		$submissionID = $posts[0]->ID;

		if(empty($submissionID)) {
			return $strReturn;
		}

		$linkURL = get_permalink($submissionID, false);

		$divToAdd = "<!-- BEGIN Show detail link for ach # $achivementID, author # $displayedID, submission postID # $submissionID --><div>" . '<a href="' . $linkURL . '">' . translate('Show Details', 'badgeos') .'</a></div><!-- END Show detail link -->';

		$htmlCommentMarker = '<!-- .badgeos-item-excerpt -->';
		$strReturn = str_replace($htmlCommentMarker, $htmlCommentMarker . $divToAdd, $strReturn);


		return $strReturn;
    }



}
