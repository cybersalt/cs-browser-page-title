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
use Joomla\Database\ParameterType;

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

        // No custom field value: either fall back to the article's own title or leave Joomla's default title alone
        if (empty($pageTitle)) {
            if (!$fallbackToTitle) {
                return;
            }

            // Respect a Browser Page Title set deliberately on the menu item that points at this article
            if ($this->activeMenuItemHasPageTitleFor($id)) {
                return;
            }

            $pageTitle = $this->getArticleTitleFromDatabase($id);

            if (empty($pageTitle)) {
                return;
            }
        }

        $this->replaceTitleInOutput($pageTitle);
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
            ->bind(':fieldId', $fieldId, ParameterType::INTEGER)
            ->bind(':itemId', $articleId, ParameterType::INTEGER);

        $db->setQuery($query);

        $result = $db->loadResult();

        return !empty($result) ? $result : null;
    }

    /**
     * Does the active menu item point at exactly this article and carry its own Browser Page Title?
     *
     * @param   int  $articleId  The article ID
     *
     * @return  bool
     */
    private function activeMenuItemHasPageTitleFor(int $articleId): bool
    {
        $item = $this->getApplication()->getMenu()->getActive();

        if (!$item) {
            return false;
        }

        $query = (array) ($item->query ?? []);

        if (
            ($query['option'] ?? '') !== 'com_content'
            || ($query['view'] ?? '') !== 'article'
            || (int) ($query['id'] ?? 0) !== $articleId
        ) {
            return false;
        }

        return trim((string) $item->getParams()->get('page_title', '')) !== '';
    }

    /**
     * Get the title to use when the custom field is empty: the article's own
     * "Browser Page Title" option if it has one, otherwise the article title.
     *
     * @param   int  $articleId  The article ID
     *
     * @return  string|null
     */
    private function getArticleTitleFromDatabase(int $articleId): ?string
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select($db->quoteName(['title', 'attribs']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = :articleId')
            ->bind(':articleId', $articleId, ParameterType::INTEGER);

        $db->setQuery($query);

        $row = $db->loadObject();

        if (!$row) {
            return null;
        }

        $attribs = json_decode((string) $row->attribs, true);

        if (\is_array($attribs) && !empty($attribs['article_page_title'])) {
            return (string) $attribs['article_page_title'];
        }

        return !empty($row->title) ? (string) $row->title : null;
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

        // Site name position, same values as Joomla's own "Site Name in Page Titles" (0 = no, 1 = before, 2 = after)
        $siteNamePosition = (int) $app->get('sitename_pagetitles', 0);

        $escapedTitle = htmlspecialchars($newTitle, ENT_QUOTES, 'UTF-8');
        $escapedSite  = htmlspecialchars((string) $siteName, ENT_QUOTES, 'UTF-8');

        // Build the full title based on site name position
        switch ($siteNamePosition) {
            case 1:
                // Site name before page title
                $fullTitle = $escapedSite . ' - ' . $escapedTitle;
                break;
            case 2:
                // Site name after page title
                $fullTitle = $escapedTitle . ' - ' . $escapedSite;
                break;
            case 0:
            default:
                // No site name
                $fullTitle = $escapedTitle;
                break;
        }

        // Replace the title tag content
        $pattern = '/<title>.*?<\/title>/is';
        $newBody = preg_replace_callback(
            $pattern,
            static function () use ($fullTitle) {
                return '<title>' . $fullTitle . '</title>';
            },
            $body,
            1
        );

        if ($newBody !== null) {
            $app->setBody($newBody);
        }
    }
}
