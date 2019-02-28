<?php

namespace CultuurNet\SearchV3Utilities;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\SearchV3\ValueObjects\Event;
use CultuurNet\SearchV3\ValueObjects\Offer;
use CultuurNet\SearchV3\ValueObjects\Place;
use IntlDateFormatter;
use Purl\Url;

/**
 * A preproccesor utility for Cultuurkuur search templates.
 */
class SearchPreprocessor
{
    use TextProcessingTrait;

    /**
     * Preprocess event data for twig templates..
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Event $event
     *   The event to process.
     * @param string $langcode
     *   The langcode to use for translations.
     * @param array $settings
     *   Optional settings for the event display.
     *
     * @return array
     *   Collection of preprocessed variables.
     */
    public function preprocessEvent(Event $event, string $langcode, array $settings = [])
    {
        $variables = [
            'id' => $event->getCdbid(),
            'name' => $event->getName()->getValueForLanguage($langcode),
            'description' => $event->getDescription() ? $event->getDescription()
                ->getValueForLanguage($langcode) : '',
            'where' => $event->getLocation() ? $this->preprocessPlace($event->getLocation(), $langcode) : null,
            'when_summary' => $this->formatEventDatesSummary($event, $langcode),
            'organizer' => ($event->getOrganizer() && $event->getOrganizer()
                    ->getName()) ? $event->getOrganizer()
                ->getName()
                ->getValueForLanguage($langcode) : null,
            'age_range' => $event->getTypicalAgeRange() ?
                $this->formatAgeRange($event->getTypicalAgeRange(), $langcode) : null,
            'themes' => $event->getTermsByDomain('theme'),
            'labels' => $event->getLabels() ?? [],
            'vlieg' => self::isVliegEvent($event),
            'uitpas' => self::isUitpasEvent($event),
        ];

        $defaultImage = $settings['image']['default_image'] ?? null;
        $image = $event->getImage() ?? $defaultImage;

        if (!empty($image)) {
            $url = Url::parse($image);
            $url->getQuery()->setData([
                'crop' => $settings['image']['crop'] ?? 'auto',
                'scale' => $settings['image']['scale'] ?? 'both',
                'width' => $settings['image']['width'] ?? '150',
                'height' => $settings['image']['height'] ?? '150',
            ]);

            $variables['image'] = $url->__toString();
            $variables['image'] = str_replace('http://', '//', $variables['image']);
            $variables['image'] = str_replace('https://', '//', $variables['image']);
        }

        $variables['copyright'] = null;
        if ($event->getMainMediaObject()) {
            $variables['copyright'] = $event->getMainMediaObject()
                ->getCopyrightHolder();
        }

        $variables['summary'] = strip_tags($variables['description']);
        if (!empty($settings['description']['characters'])) {
            $originalSummary = $variables['summary'];
            $variables['summary'] = $this->createSummary($variables['summary'], $settings['description']['characters']);
            if (strlen($variables['summary']) < strlen($originalSummary)) {
                // Only add ellipsis if the summary does not end at the end of sentence.
                $punctuation = ['.', '!', '?', '。', '؟ '];
                $variables['summary'] .= in_array(substr($variables['summary'], -1), $punctuation) ? '' : '...';
            }
        }

        $languageIconKeywords = [
            'één taalicoon' => 1,
            'twee taaliconen' => 2,
            'drie taaliconen' => 3,
            'vier taaliconen' => 4,
        ];

        // Search for language keywords. Take the highest value of all items that match..
        $totalLanguageIcons = 0;
        if (!empty($variables['labels'])) {
            foreach ($languageIconKeywords as $keyword => $value) {
                if (in_array($keyword, $variables['labels'])) {
                    $totalLanguageIcons = $value;
                }
            }
        }

        // Strip not allowed types.
        if (!empty($variables['labels']) &&
            !empty($settings['labels']['limit_labels']) &&
            $settings['labels']['limit_labels']['enabled']
        ) {
            $allowedLabels = explode(', ', $settings['labels']['limit_labels']['labels']);
            $variables['labels'] = array_intersect($variables['labels'], $allowedLabels);
        }

        // Add types as first labels, if enabled.
        if (!empty($settings['type']['enabled'])) {
            $types = $event->getTermsByDomain('eventtype');
            $variables['types'] = [];
            $typeLabels = [];
            if (!empty($types)) {
                foreach ($types as $type) {
                    $variables['types'][] = $type;
                }
            }
        }

        // Age from.
        $variables['for_kids'] = false;

        $variables['age_from'] = null;
        if ($range = $event->getTypicalAgeRange()) {
            if ($range !== '-') {
                // Explode range on dash.
                $explRange = explode('-', $range);
                $variables['age_from'] = $explRange[0];

                if ($explRange[0] < 12) {
                    $variables['for_kids'] = true;
                }
            }
        }

        $variables['prices'] = [];
        if ($priceInfo = $event->getPriceInfo()) {
            $prices = [];
            foreach ($priceInfo as $price) {
                $value = $price->getPrice() > 0 ? '&euro; ' .
                    str_replace('.', ',', (float) $price->getPrice()) : 'gratis';
                $variables['prices'][] = [
                    'price' => $value,
                    'info' => $price->getName()->getValueForLanguage($langcode),
                ];
            }
        }

        return $variables;
    }

    /**
     * Preprocess an event detail page.
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Event $event
     *   The event to process.
     * @param string $langcode
     *   The langcode to use for translations.
     * @param array $settings
     *   Optional settings to use.
     *
     * @return array
     *   Collection of preprocessed variables.
     */
    public function preprocessEventDetail(Event $event, string $langcode, array $settings = [])
    {
        $variables = $this->preprocessEvent($event, $langcode, $settings);

        $variables['summary'] = '';
        if (!empty($settings['description']['characters'])) {
            $variables['summary'] = $this->createHtmlSummary(
                $variables['description'],
                $settings['description']['characters']
            );

            if (strlen($variables['summary']) === strlen($variables['description'])) {
                $variables['summary'] = '';
            }
        }

        $variables['when_details'] = $this->formatEventDatesDetail($event, $langcode);

        // Directions are done via direct link too google.
        if ($event->getLocation()) {
            $directionData = '';
            if ($event->getLocation()->getGeo()) {
                $geoInfo = $event->getLocation()->getGeo();
                $directionData = $geoInfo->getLatitude() . ',' . $geoInfo->getLongitude();
            } else {
                /** @var \CultuurNet\SearchV3\ValueObjects\TranslatedAddress $address */
                $address = $event->getLocation()->getAddress();
                if ($translatedAddress = $address->getAddressForLanguage($langcode)) {
                    $directionData = $translatedAddress->getStreetAddress() . ' '
                        . $translatedAddress->getPostalCode() . ' ' . $translatedAddress->getAddressLocality();
                }
            }

            $variables['directions_link'] = 'https://www.google.com/maps/dir/?api=1&destination='
                . urlencode($directionData);
        }

        // Booking information.
        $variables['booking_info'] = [];
        if ($event->getBookingInfo()) {
            $bookingInfo = $event->getBookingInfo();
            $variables['booking_info'] = [];
            if ($bookingInfo->getEmail()) {
                $variables['booking_info']['email'] = $bookingInfo->getEmail();
            }
            if ($bookingInfo->getPhone()) {
                $variables['booking_info']['phone'] = $bookingInfo->getPhone();
            }
            if ($bookingInfo->getUrl()) {
                $variables['booking_info']['url'] = [
                    'url' => $bookingInfo->getUrl(),
                    'label' => !empty($bookingInfo->getUrlLabel()
                        ->getValueForLanguage($langcode)) ? $bookingInfo->getUrlLabel()
                        ->getValueForLanguage($langcode) : $bookingInfo->getUrl(),
                ];
            }

            $dateFormatter = new IntlDateFormatter(
                'nl_NL',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                'd MMMM Y'
            );
            if ($bookingInfo->getAvailabilityStarts()) {
                $variables['booking_info']['start_date'] =
                    $dateFormatter->format($bookingInfo->getAvailabilityStarts());
            }
            if ($bookingInfo->getAvailabilityEnds()) {
                $variables['booking_info']['end_date'] =
                    $dateFormatter->format($bookingInfo->getAvailabilityStarts());
            }
        }

        // Contact info.
        $variables['contact_info'] = [];
        $variables['links'] = [];
        if ($event->getContactPoint()) {
            $contactPoint = $event->getContactPoint();
            $variables['contact_info']['emails'] = $contactPoint->getEmails();
            $variables['contact_info']['phone_numbers'] = $contactPoint->getPhoneNumbers();
            $variables['links'] = $contactPoint->getUrls();
        }

        return $variables;
    }

    /**
     * Preprocess a place.
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Place $place
     * @param $langcode
     *
     * @return array
     */
    public function preprocessPlace(Place $place, $langcode)
    {
        $variables = [];
        $variables['name'] = $place->getName()->getValueForLanguage($langcode);
        $variables['address'] = [];
        if ($address = $place->getAddress()) {
            if ($translatedAddress = $address->getAddressForLanguage($langcode)) {
                $variables['address']['street'] = $translatedAddress->getStreetAddress() ?? '';
                $variables['address']['postalcode'] = $translatedAddress->getPostalCode() ?? '';
                $variables['address']['city'] = $translatedAddress->getAddressLocality() ?? '';
            }
        }

        if ($geoInfo = $place->getGeo()) {
            $variables['geo'] = [];
            $variables['geo']['latitude'] = $geoInfo->getLatitude();
            $variables['geo']['longitude'] = $geoInfo->getLongitude();
        }

        return $variables;
    }

    /**
     * Format all the event dates to 1 summary variable.
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Event $event
     *
     * @return string
     */
    protected function formatEventDatesSummary(Event $event, string $langcode)
    {
        // Switch the time locale to the requested langcode.
        switch ($langcode) {
            case 'fr':
                $locale = 'fr_FR';
                break;

            case 'nl':
            default:
                $locale = 'nl_NL';
                break;
        }

        $calendarPlainTextFormatter = new CalendarPlainTextFormatter($locale);
        return $calendarPlainTextFormatter->format($event, 'md');
    }

    /**
     * Format the event dates for the detail page.
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Event $event
     * @param string $langcode
     *
     * @return string
     */
    protected function formatEventDatesDetail(Event $event, string $langcode)
    {
        // Switch the time locale to the requested langcode.
        switch ($langcode) {
            case 'fr':
                $locale = 'fr_FR';
                break;

            case 'nl':
            default:
                $locale = 'nl_NL';
                break;
        }

        $calendarPlainTextFormatter = new CalendarPlainTextFormatter($locale);
        $calendarHTMLFormatter = new CalendarHTMLFormatter($locale);

        if ($event->getCalendarType() === Offer::CALENDAR_TYPE_MULTIPLE) {
            return $calendarHTMLFormatter->format($event, 'lg');
        } else {
            return $calendarPlainTextFormatter->format($event, 'lg');
        }
    }

    /**
     * Format an age range value according to langcode.
     *
     * @param string $range
     * @param string $langcode
     *
     * @return string
     */
    protected function formatAgeRange($range, string $langcode)
    {
        // Check for empty range values.
        if ($range == '-') {
            return null;
        }
        // Explode range on dash.
        $explRange = explode('-', $range);

        if (empty($explRange[1]) || $explRange[0] === $explRange[1]) {
            return $explRange[0] . ' jaar';
        }

        // Build range string according to language.
        return "Vanaf $explRange[0] jaar tot $explRange[1] jaar.";
    }

    /**
     * Check if event is considered a "Vlieg" event and return either
     * the minimum age or a boolean value.
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Event $event
     *
     * @return bool|string
     */
    public static function isVliegEvent(Event $event)
    {
        $range = $event->getTypicalAgeRange();
        $labels = $event->getLabels();
        $labels = array_merge($labels, $event->getHiddenLabels());

        // Check age range if there is one.
        if ($range) {
            // Check for empty range values.
            if ($range !== '-') {
                // Explode range on dash.
                $explRange = explode('-', $range);
                // Check min age and return it if it's lower than 12.
                if ($explRange[0] < 12) {
                    return "$explRange[0]+";
                }
            }
        }

        // Check for certain labels that also determine "Vlieg" events.
        return ($labels && count(array_intersect($labels, [
            'ook voor kinderen',
            'uit met vlieg',
        ])) > 0 ? '0+' : false);
    }

    /**
     * Check if event is considered an "Uitpas" event.
     *
     * @param \CultuurNet\SearchV3\ValueObjects\Event $event
     *
     * @return bool
     */
    public static function isUitpasEvent(Event $event)
    {
        $labels = $event->getLabels();
        $labels = array_merge($labels, $event->getHiddenLabels());

        // Check for label values containing "Uitpas".
        if ($labels) {
            foreach ($labels as $label) {
                if (stripos($label, 'uitpas') !== false || stripos($label, 'paspartoe') !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
