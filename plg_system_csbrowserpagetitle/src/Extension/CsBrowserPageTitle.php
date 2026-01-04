<?php

/**
 * @package     Cybersalt.Plugin
 * @subpackage  System.CsBrowserPageTitle
 *
 * @copyright   (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Cybersalt\Plugin\System\CsBrowserPageTitle\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Event\SubscriberInterface;
use Joomla\Database\DatabaseAwareTrait;

/**
 * Plugin to set browser page title from a custom field value
 */
class CsBrowserPageTitle extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender' => 'onAfterRender',
        ];
    }

    /**
     * Modifies the page title after all rendering is complete
     *
     * @return  void
     */
    public function onAfterRender(): void
    {
        $app = $this->getApplication();

        // Only run on the frontend site
        if (!$app->isClient('site')) {
            return;
        }

        // Check if we're viewing a single article
        $option = $app->input->get('option');
        $view = $app->input->get('view');
        $id = $app->input->getInt('id');

        if ($option !== 'com_content' || $view !== 'article' || !$id) {
            return;
        }

        // Get the custom field ID from plugin parameters
        $customFieldId = (int) $this->params->get('custom_field_id', 0);
        $fallbackToTitle = (bool) $this->params->get('fallback_to_title', 1);

        // If no field is selected, do nothing
        if ($customFieldId === 0) {
            return;
        }

        // Get the custom field value from the database
        $pageTitle = $this->getFieldValueFromDatabase($id, $customFieldId);

        // If no custom field value and fallback is enabled, leave default title
        if (empty($pageTitle) && $fallbackToTitle) {
            return;
        }

        // If we have a page title from the custom field, replace it in the HTML
        if (!empty($pageTitle)) {
            $this->replaceTitleInOutput($pageTitle);
        }
    }

    /**
     * Get the field value directly from the database
     *
     * @param   int  $articleId  The article ID
     * @param   int  $fieldId    The custom field ID
     *
     * @return  string|null
     */
    private function getFieldValueFromDatabase(int $articleId, int $fieldId): ?string
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select($db->quoteName('value'))
            ->from($db->quoteName('#__fields_values'))
            ->where($db->quoteName('field_id') . ' = :fieldId')
            ->where($db->quoteName('item_id') . ' = :itemId')
            ->bind(':fieldId', $fieldId)
            ->bind(':itemId', $articleId);

        $db->setQuery($query);

        $result = $db->loadResult();

        return !empty($result) ? $result : null;
    }

    /**
     * Replace the title tag in the rendered output
     *
     * @param   string  $newTitle  The new page title
     *
     * @return  void
     */
    private function replaceTitleInOutput(string $newTitle): void
    {
        $app = $this->getApplication();
        $body = $app->getBody();

        // Get site name from global config
        $siteName = $app->get('sitename', '');

        // Get the site name position setting (0 = before, 1 = after, 2 = none)
        $siteNamePosition = $app->get('sitename_pagetitles', 0);

        // Build the full title based on site name position
        switch ($siteNamePosition) {
            case 1:
                // Site name after page title
                $fullTitle = htmlspecialchars($newTitle, ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
                break;
            case 2:
                // No site name
                $fullTitle = htmlspecialchars($newTitle, ENT_QUOTES, 'UTF-8');
                break;
            case 0:
            default:
                // Site name before page title
                $fullTitle = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($newTitle, ENT_QUOTES, 'UTF-8');
                break;
        }

        // Replace the title tag content
        $pattern = '/<title>.*?<\/title>/is';
        $replacement = '<title>' . $fullTitle . '</title>';
        $newBody = preg_replace($pattern, $replacement, $body, 1);

        if ($newBody !== null) {
            $app->setBody($newBody);
        }
    }
}
