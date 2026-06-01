<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2025 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace GSD;

defined('_JEXEC') or die('Restricted Access');

use Joomla\Registry\Registry;
use Joomla\CMS\Text\Text;

/**
 *  Google Structured Data JSON generator
 */
class JSON
{
	/**
	 *  Content Type Data
	 *
	 *  @var  object
	 */
	private $data;

    /**
     *  List of available content types
     *
     *  @var  array
     */
    private $contentTypes = [
        
        'book',
        'course',
        'event',
        'product',
        'movie',
        'recipe',
        'review',
        'factcheck',
        'video',
        'jobposting',
        'custom_code',
        'faq',
        'howto',
        'localbusiness',
        'service',
        'person',
        'organization',
        
        'article'
    ];

	/**
	 *  Class Constructor
	 *
	 *  @param  object  $data
	 */
	public function __construct($data = null)
	{
		$this->setData($data);
	}

    /**
     *  Get Content Types List
     *
     *  @return  array
     */
    public function getContentTypes()
    {
        $types = $this->contentTypes;
        asort($types);

        // Move Custom Code option to the end
        if ($customCodeIndex = array_search('custom_code', $types))
        {
            unset($types[$customCodeIndex]);
            $types[] = 'custom_code';
        }

        return $types;
    }

	/**
	 *  Set Data
	 *
	 *  @param  array  $data
	 */
	public function setData($data)
	{
		if (is_array($data))
		{
			$this->data = new Registry($data);
		} else 
        {
            $this->data = $data;
        }

		return $this;
	}

	/**
	 *  Get Content Type result
	 *
	 *  @return  string
	 */
	public function generate()
	{
        $contentTypeMethod = 'contentType' . $this->data->get('contentType');

        // Make sure we have a valid Content Type
		if (!method_exists($this, $contentTypeMethod) || !$content = $this->$contentTypeMethod())
		{
            return;
		}

        // In case we have a string (See Custom Code), return the original content.
        if (is_string($content))
        {
            return $content;
        }

        Helper::event('onGSDSchemaBeforeGenerate', [&$content, $this->data]);

        // Sanity check
        if (!$content)
        {
            return;
        }

        // Remove null and empty properties
        $content = $this->clean($content);

        // In case we have an array, transform it into JSON-LD format.
        // Always prepend the @context property
        $content = ['@context' => 'https://schema.org'] + $content;

        // We do not use JSON_NUMERIC_CHECK here because it doesn't respect numbers starting with 0. 
        // Bug: https://bugs.php.net/bug.php?id=70680
        $json_string = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Detect issues with the encoding
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $json_string = Text::sprintf('JSON Error: %s.', json_last_error_msg()) . ' ' . $content;
        }

return '
<script type="application/ld+json" data-type="gsd">
'
    . $json_string .
'
</script>';
    }
    
    /**
     * Filter resursively an array by removing empty, false and null properties while preserving 0 values.
     *
     * @param  array $input
     *
     * @return array
     */
    private function clean($input)
    { 
        foreach ($input as &$value) 
        {
            if (is_array($value)) 
            { 
                $value = self::clean($value);
            }
        }

        // We use a custom callback here because the default behavior of array_filter removes 0 values as well.
        return array_filter($input, function($value)
        {
            // Remove also orphan array properties
            if (is_array($value) && count($value) == 1 && isset($value['@type']))
            {
                return false;
            }

            return ($value !== null && $value !== false && $value !== ''); 
        });
    }

    /**
     *  Constructs the Preson Content Type
     *
     *  @return  array
     */
    private function contentTypePerson()
    {
        $content = [
            '@type' => 'Person',
            '@id' => $this->data->get('id'),
            'url' => $this->data->get('url'),
            'name' => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'honorificPrefix' => $this->data->get('honorificPrefix'),
            'honorificSuffix' => $this->data->get('honorificSuffix'),
            'alternateName' => $this->data->get('alternateName'),
            'additionalName' => $this->data->get('additionalName'),
            'givenName' => $this->data->get('givenName'),
            'familyName' => $this->data->get('familyName'),
            'address' => $this->getPostalAddress(),
            'nationality' => $this->data->get('nationality'),
            'email' => $this->data->get('email'),
            'telephone' => $this->data->get('telephone'),
            'gender' => $this->data->get('gender'),
            'birthDate' => $this->data->get('birthDate'),
            'memberOf' => $this->data->get('memberOf'),
            'image' => $this->data->get('image'),
            'jobTitle' => $this->data->get('jobTitle'),
            'affiliation' => $this->data->get('affiliation'),
            'alumniOf' => $this->data->get('alumniOf'),
            'award' => $this->data->get('award'),
            'knowsAbout' => $this->data->get('knowsAbout'),
            'hasCredential' => $this->data->get('hasCredential'),
            'hasOccupation' => [
                '@type' => 'Occupation',
                'name' => $this->data->get('occupationName'),
                'description' => $this->data->get('occupationDescription'),
                'educationRequirements' => $this->data->get('educationRequirements'),
                'experienceRequirements' => $this->data->get('experienceRequirements'),
                'occupationLocation' => [
                    '@type' => 'Country',
                    'name' => $this->data->get('addressCountry')
                ],
                'estimatedSalary' => [
                    '@type' => 'MonetaryAmountDistribution',
                    'name' => 'base',
                    'duration' => 'P1Y',
                    'minValue' => is_array($this->data->get('offerPrice')) ? $this->data->get('offerPrice')[0] : null,
                    'maxValue' => is_array($this->data->get('offerPrice')) ? $this->data->get('offerPrice')[1] : null,
                    'currency' => $this->data->get('currency'),
                ]
            ],
        ];

        if (empty(array_filter([
            $this->data->get('occupationName'),
            $this->data->get('occupationDescription'),
            $this->data->get('educationRequirements'),
            $this->data->get('experienceRequirements'),
            $this->data->get('offerPrice'),
            $this->data->get('currency')
        ])))
        {
            unset($content['hasOccupation']);
        }

        if ($this->data->get('type') !== 'Person')
        {
            $content['additionalType'] = $this->data->get('type');
        }

        if ($worksFor = $this->data->get('worksFor', ''))
        {
            $content['worksFor'] = [
                '@type' => 'Organization',
                'name' => $worksFor
            ];
        }

        if ($sameAs = (array) $this->data->get('sameAs'))
        {
            $content['sameAs'] = array_values($sameAs);
        }

        return $content;
    }

    /**
     * Constructs the HowTo Schema Type
     * 
     * @return  array
     */
    private function contentTypeHowTo()
    {
        $steps = array_map(function($step)
        {
            return array_merge(['@type' => 'HowToStep'], $step);
        }, $this->data->get('step'));

        $tools = array_map(function($tool)
        {
            return [
                '@type' => 'HowToTool',
                'name' => $tool
            ];
        }, (array) $this->data->get('tool'));

        $supply = array_map(function($supply)
        {
            return [
                '@type' => 'HowToSupply',
                'name' => $supply
            ];
        }, (array) $this->data->get('supply'));

        return [
            '@type' => 'HowTo',
            'image' => [
                '@type' => 'ImageObject',
                'url' => $this->data->get('image')
            ],
            'name' => $this->data->get('name'),
            'totalTime' => $this->data->get('totalTime'),
            'estimatedCost' => [
                '@type' => 'MonetaryAmount',
                'currency' => $this->data->get('estimatedCostCurrency'),
                'value' => $this->data->get('estimatedCost')
            ],
            'supply' => $supply,
            'tool' => $tools,
            'step' => $steps
        ];
    }   

    /**
     * Constructs the FAQ Snippet
     * 
     * @return  array
     */
    private function contentTypeFAQ()
    {
        $faq = $this->data->get('faqs');

        // If there are no FAQ data, return
        if (count($faq) == 0)
        {
            return;
        }

        $faqData = [];

        foreach ($faq as $item)
        {
            $faqData[] = [
                '@type' => 'Question',
                'name'  => $item['question'],
                'acceptedAnswer' => [
                    '@type'      => 'Answer',
                    'text'       => $item['answer']
                ]
            ];
        }

        return [
            '@type'      => 'FAQPage',
            'mainEntity' => $faqData
        ];
    }

    /**
     *  Constructs the Breadcrumbs Snippet
     *
     *  @return  array
     */
    private function contentTypeBreadcrumbs()
    {
        $crumbs = $this->data->get('crumbs');
        
        if (!is_array($crumbs))
        {
            return;
        }

        $crumbsData = [];

        foreach ($crumbs as $key => $value)
        {
            $crumbsData[] = [
                '@type'    => 'ListItem',
                'position' => ($key + 1),
                'name'     => $value->name,
                'item'     => $value->link
            ];
        }

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbsData
        ];
    }

	/**
	 *  Constructs the Article Content Type
	 *
	 *  @return  array
	 */
	private function contentTypeArticle()
	{
        $content = [
            '@type' => $this->data->get('type', 'Article'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $this->data->get('url')
            ],
            'headline'    => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'image' => [
                '@type'  => 'ImageObject',
                'url'    => $this->data->get('image')
            ]
        ];

		// Publisher
		if ($this->data->get('publisherName'))
		{
            $content = array_merge($content, [
                'publisher' => [
                    '@type' => 'Organization',
                    'name'  => $this->data->get('publisherName'),
                    'logo'  => [
                        '@type'  => 'ImageObject',
                        'url'    => $this->data->get('publisherLogo')
                    ]
                ]
            ]);  
		}

        // Add author
        $this->addAuthor($content);

        return $this->addDate($content);
	}

    /**
     *  Constructs the Organization Content Type
     *  https://developers.google.com/search/docs/appearance/structured-data/organization
     * 
     *
     *  @return  array
     */
    private function contentTypeOrganization()
    {
        $content = [
            '@type' => $this->data->get('type'),
            '@id'   => $this->data->get('id'),
            'name'  => $this->data->get('name'),
            'alternateName'  => $this->data->get('alternateName'),
            'legalName'  => $this->data->get('legalName'),
            'description' => $this->data->get('description'),
            'email' => $this->data->get('email'),
            'url' => $this->data->get('url'),
            'telephone' => $this->data->get('telephone'),
            'foundingDate' => $this->data->get('foundingDate'),
            'taxID' => $this->data->get('taxID'),
            'vatID' => $this->data->get('vatID'),
            'iso6523Code' => $this->data->get('iso6523Code'),
            'duns' => $this->data->get('duns'),
            'leiCode' => $this->data->get('leiCode'),
            'naics' => $this->data->get('naics'),
            'numberOfEmployees' => $this->data->get('numberOfEmployees'),
            'logo' => $this->data->get('logo'),
            'address' => $this->getPostalAddress(),
        ];

        if ($sameAs = (array) $this->data->get('sameAs', []))
        {
            $content['sameAs'] = array_values($sameAs);
        }

        // Aggregate Rating
        $this->addRating($content);

        return $content;
    }

    
    /**
     *  Constructs the Business Listing Content Type
     *  https://developers.google.com/search/docs/data-types/local-businesses
     *
     *  @return  array
     */
    private function contentTypeLocalBusiness()
    {
        $content = [
            '@type' => $this->data->get('type'),
            // Neither Rich Results Test or the deprecated tool, Google Structured Data Testing Tool doesn't throw a warning any more if the @id property is missing.            
            '@id'   => $this->data->get('id'),
            'name'  => $this->data->get('name'),
            'image' => $this->data->get('image'),
            'url' => $this->data->get('url'),
            'telephone' => $this->data->get('telephone'),
            'priceRange' => $this->data->get('priceRange'),
            'address' => $this->getPostalAddress()
        ];

        // Map coordinates
        $coords = $this->data->get('geo');
        if ($coords && !empty($coords) && count($coords) == 2)
        {
            $content['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $coords[0],
                'longitude' => $coords[1]
            ];
        }
        
        // Opening Hours
        if ($this->data->get('openinghours'))
        {
            $openingHours = $this->getOpeningHours($this->data->get('openinghours'));
            $content = array_merge($content, $openingHours);
        }

        // Food-based business types
        $content['servesCuisine'] = $this->data->get('servesCuisine');
        $content['menu'] = $this->data->get('menu');

        // Add Review
        $this->addReview($content);

        // Aggregate Rating
        $this->addRating($content);

        return $content;
    }

    /**
     *  Constructs the Product Content Type
     *  https://developers.google.com/search/docs/data-types/products
     *
     *  @return  array
     */
    private function contentTypeProduct()
    {
        $content = [
            '@type'       => 'Product',
            'productID'   => $this->data->get('mpn'),
            'name'        => $this->data->get('title'),
            'image'       => $this->data->get('image'),
            'description' => $this->data->get('description'),
            'sku'         => $this->data->get('sku'),
            'mpn'         => $this->data->get('mpn'),
            'gtin'        => $this->data->get('gtin'),
            'google_product_category' => $this->data->get('google_product_category')
        ];

        // Weight
        if ($this->data->get('weight'))
        {
            $content = array_merge($content, [
                'weight' => [
                    '@type' => 'QuantitativeValue',
                    'value' => $this->data->get('weight'),
                    'unitText' => $this->data->get('weightUnit')
                ]
            ]);
        }

        // Brand
        if ($this->data->get('brand'))
        {
            $content = array_merge($content, [
                'brand' => [
                    '@type' => 'Brand',
                    'name'  => $this->data->get('brand')
                ]
            ]);
        }

        // Offer / Pricing
        if ($price = $this->data->get('offerPrice'))
        {
            $offerCommon = [
                'priceCurrency'   => $this->data->get('currency'),
                'url'             => $this->data->get('url'),
                'itemCondition'   => $this->data->get('offerItemCondition'),
                'availability'    => $this->data->get('offerAvailability'),
                'priceValidUntil' => $this->data->get('priceValidUntil')
            ];

            if (is_array($price))
            {
                $offer = [
                    '@type' => 'AggregateOffer',
                    'offerCount' => $this->data->get('offerCount', 1),
                    'lowPrice' => $price[0],
                    'highPrice' => $price[1]
                ];
            } else 
            {
                $offer = [
                    '@type' => 'Offer',
                    'price' => $price
                ];
            }

            $content['offers'] = array_merge($offer, $offerCommon);
        }

        // Add Review
        $this->addReview($content);
        
        // Aggregate Rating
        $this->addRating($content);

        return $content;
    }

    /**
     * Adds review data to content
     * 
     * @param   array  $content
     * 
     * @return  void
     */
    private function addReview(&$content)
    {
        if (!$this->data->get('reviews') || !$this->data->get('reviewCount'))
        {
            return;
        }

        // Review
        $reviews     = $this->data->get('reviews');
        $bestRating  = $this->data->get('bestRating', 5);
        $worstRating = $this->data->get('worstRating', 0);

        $review_data = [];
        foreach ($reviews as $review)
        {
            $rating = $review['rating'];

            $review_data[] = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name'  => $review['author'],
                ],
                'datePublished' => $review['datePublished'],
                'description' => $review['description'],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'bestRating'  => $bestRating,
                    'ratingValue' => $rating,
                    'worstRating' => $worstRating
                ]
            ];
        }

        $content = array_merge($content, [
            'review' => $review_data
        ]);
    }

	/**
	 *  Constructs the Book Content Type
	 *  https://developers.google.com/search/docs/appearance/structured-data/book
	 *
	 *  @return  array
	 */
	private function contentTypeBook()
	{
        $workExample = [
            '@type' => 'Book',
            '@id' => $this->data->get('id'),
            'bookFormat' => $this->data->get('bookFormat'),
            'inLanguage' => $this->data->get('inLanguage'),
            'isbn' => $this->data->get('isbn'),
            'url' => $this->data->get('url'),
            'bookEdition' => $this->data->get('edition'),
            'datePublished' => $this->data->get('datePublished'),
            'potentialAction' => [
                '@type' => $this->data->get('potentialAction'),
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->data->get('actionURL'),
                    'actionPlatform' => [
                        'http://schema.org/DesktopWebPlatform',
                        'http://schema.org/AndroidPlatform',
                        'http://schema.org/IOSPlatform'
                    ]
                ]
            ]
        ];
        
        // Add author to $workExample
        $this->addAuthor($workExample);
        
        $content = [
            '@type' => 'Book',
            '@id' => $this->data->get('id'),
            'url' => $this->data->get('url'),
            'name' => $this->data->get('title'),
            'image' => $this->data->get('image'),
            'sameAs' => $this->data->get('referenceURL'),
            'inLanguage' => $this->data->get('inLanguage'),
            'workExample' => $workExample
        ];

        // Add author
        $this->addAuthor($content);

        // Add identifier to workExample property
        $identifiers = [];
        if ($identifier_oclc_number = $this->data->get('identifier_oclc_number'))
        {
            $identifiers['OCLC_NUMBER'] = $identifier_oclc_number;
        }
        if ($identifier_lccn = $this->data->get('identifier_lccn'))
        {
            $identifiers['LCCN'] = $identifier_lccn;
        }
        if ($identifier_jp_e_code = $this->data->get('identifier_jp_e_code'))
        {
            $identifiers['JP_E-CODE'] = $identifier_jp_e_code;
        }
        if (count($identifiers))
        {
            $values = [];
            
            foreach ($identifiers as $key => $value)
            {
                $values[] = [
                    '@type' => 'PropertyValue',
                    'propertyID' => $key,
                    'value' => $value
                ];
            }

            if ($values)
            {
                $content['workExample'] = array_merge($content['workExample'], [
                    'identifier' => $values
                ]);
            }
        }

        // Add Aggregate Rating
        $this->addRating($content);

        return $content;
    }

	/**
	 *  Constructs the Event Content Type
	 *  https://developers.google.com/search/docs/data-types/events
	 *
	 *  @return  array
	 */
	private function contentTypeEvent()
	{
        $content = [
            '@type'       => 'Event',
            'name'        => $this->data->get('title'),
            'image'       => $this->data->get('image'),
            'description' => $this->data->get('description'),
            'url'         => $this->data->get('url'),
            'startDate'   => $this->data->get('startDate'),
            'endDate'     => $this->data->get('endDate'),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $this->data->get('eventAttendanceMode', 'https://schema.org/OfflineEventAttendanceMode'),
            'offers' => [
                '@type'         => 'Offer',
                'url'           => $this->data->get('url'),
                'availability'  => $this->data->get('offerAvailability'),
                'validFrom'     => $this->data->get('startDateTime'),
                'price'         => $this->data->get('price'),
                'priceCurrency' => $this->data->get('offerCurrency'),
                'inventoryLevel' => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'QuantitativeValue',
                    'value'    => $this->data->get('offerInventoryLevel'),
                    'unitText' => 'Tickets'
                ]
            ]
        ];

        // Psysical Location
        if ($this->data->get('locationName'))
        {
            $content['location'] = [
                '@type'   => 'Place',
                'name'    => $this->data->get('locationName'),
                'address' => $this->getPostalAddress()
            ];
        }

        // Online Event
        if ($online_event_url = $this->data->get('online_url'))
        {
            $online_event = [
                '@type' => 'VirtualLocation',
                'url'   => $online_event_url
            ];

            if (isset($content['location']))
            {
                $content['location']['url'] = $online_event_url;
            } else 
            {
                $content['location'] = $online_event;
            }
        }

        // Performer
        if ($this->data->get('performerName'))
        {
            $content = array_merge($content, [
                'performer' => [
                    '@type' => $this->data->get('performerType'),
                    'name'  => $this->data->get('performerName'),
                    'url'  => $this->data->get('performerURL')
                ],
            ]);
        }

        // Organizer
        if ($this->data->get('organizerName'))
        {
            $content = array_merge($content, [
                'organizer' => [
                    '@type' => $this->data->get('organizerType'),
                    'name'  => $this->data->get('organizerName'),
                    'url'  => $this->data->get('organizerURL')
                ],
            ]);
        }

		return $content;
	}

	/**
	 *  Constructs the Movie Content Type
	 *  https://developers.google.com/search/docs/data-types/movie
	 *
	 *  @return  array
	 */
	private function contentTypeMovie()
	{
        $content = [
            '@type'       => 'Movie',
            'url'         => $this->data->get('url'),
            'name'        => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'image'       => $this->data->get('image'),
            'dateCreated' => $this->data->get('datePublished'),
        ];

        // Duration
        if ($duration = $this->data->get('duration'))
        {
            $content['duration'] = $duration;
        }

        // Genre
        if ($genre = $this->data->get('genre'))
        {
            $genreData = [];

            foreach ($genre as $key => $g)
            {
                if (empty($g->name))
                {
                    continue;
                }

                $genreData['genre'][] = trim($g->name);
            }

            $content = array_merge($content, $genreData);
        }

        // Creators
        if ($creators = $this->data->get('creators'))
        {
            $creatorsData = [];

            foreach ($creators as $key => $creator)
            {
                if (empty($creator->name))
                {
                    continue;
                }

                $creatorsData['creator'][] = [
                    '@type' => 'Person',
                    'name'  => trim($creator->name)
                ];
            }

            $content = array_merge($content, $creatorsData);
        }

        // Directors
        if ($directors = $this->data->get('directors'))
        {
            $directorsData = [];

            foreach ($directors as $key => $director)
            {
                if (empty($director->name))
                {
                    continue;
                }

                $directorsData['director'][] = [
                    '@type' => 'Person',
                    'name'  => trim($director->name)
                ];
            }

            $content = array_merge($content, $directorsData);
        }

        // Actors
        if ($actors = $this->data->get('actors'))
        {
            $actorsData = [];

            foreach ($actors as $key => $actor)
            {
                if (empty($actor->name))
                {
                    continue;
                }

                $actorsData['actor'][] = [
                    '@type' => 'Person',
                    'name'  => trim($actor->name)
                ];
            }

            $content = array_merge($content, $actorsData);
        }

        // Trailer
        $content['trailer'] = [
            '@type' => 'VideoObject',
            'embedUrl' => $this->data->get('trailerUrl'),
            'name' => $this->data->get('title'),
            'thumbnail' => [
                '@type' => 'ImageObject',
                'contentUrl' => $this->data->get('image')
            ],
            'thumbnailUrl' => $this->data->get('image'),
            'description' => $this->data->get('description'),
            'uploadDate' => $this->data->get('datePublished')
        ];

        // Add Aggregate Rating
        $this->addRating($content);
        
        $this->addReview($content);

		return $content;
	}

    /**
     * Gets the Opening Hours for the Business Listing Type
     * 
     * @param   array  $openingHours
     * 
     * @return  array
     */
    private function getOpeningHours($openingHours)
    {
        $content = [];

        // get the hours available
        // 0: No hours specified
        // 1: Always Open
        // 2: Specific Hours
        $hoursAvailable = (int) $openingHours->option;

        // return if no hours are specified
        if ($hoursAvailable == 0)
        {
            return $content;
        }

        unset($openingHours->option);
        $weekdays = array_map('ucfirst', array_keys((array) $openingHours));

        // Always Open
        if ($hoursAvailable == 1)
        {
            $content['openingHoursSpecification'] = [
                '@type'     =>  'OpeningHoursSpecification',
                'dayOfWeek' => $weekdays,
                'opens'     => '00:00',
                'closes'    => '23:59'
            ];

            return $content;
        }

        // Selected Dates
        $openingHoursData = [];

        foreach ($openingHours as $day_name => $day_options)
        {
            $day_name = ucfirst($day_name);

            if (!isset($day_options->enabled) || !$day_options->enabled)
            {
                continue;
            }

            // If no hours are set, assume open 24 hours
            if (empty($day_options->start) && empty($day_options->end))
            {
                $openingHoursData[] = [
                    '@type'       => 'OpeningHoursSpecification',
                    'dayOfWeek'   => $day_name,
                    'opens'       => '00:00',
                    'closes'      => '23:59'
                ];

                continue;
            }
            
            $openingHoursData[] = [
                '@type'       => 'OpeningHoursSpecification',
                'dayOfWeek'   => $day_name,
                'opens'       => $day_options->start,
                'closes'      => $day_options->end
            ];

            if (empty($day_options->start1) || empty($day_options->end1))
            {
                continue;
            }

            $openingHoursData[] = [
                '@type'       => 'OpeningHoursSpecification',
                'dayOfWeek'   => $day_name,
                'opens'       => $day_options->start1,
                'closes'      => $day_options->end1
            ];
        }

        if ($openingHoursData)
        {
            $content['openingHoursSpecification'] = $openingHoursData;             
        }

        return $content;
    }

	/**
	 *  Constructs the Recipe Content Type
	 *  https://developers.google.com/search/docs/data-types/recipes
	 *
	 *  @return  array
	 */
	private function contentTypeRecipe()
	{
        $content = [
            '@type'          => 'Recipe',
            'name'           => $this->data->get('title'),
            'image'          => $this->data->get('image'),
            'description'    => $this->data->get('description'),
            'prepTime'       => $this->data->get('prepTime'),
            'cookTime'       => $this->data->get('cookTime'),
            'totalTime'      => $this->data->get('totalTime'),
            'keywords'       => $this->data->get('keywords'),
            'recipeCuisine'  => $this->data->get('cuisine'),
            'recipeCategory' => $this->data->get('category'),
            'recipeYield'        => $this->data->get('yield'),
            'recipeIngredient'   => $this->data->get('ingredient'),
            'recipeInstructions' => $this->data->get('instructions')
        ];

        if ($this->data->get('calories'))
        {
            $content = array_merge($content, [
                'nutrition'    => [
                    '@type'    => 'NutritionInformation',
                    'calories' => $this->data->get('calories')
                ], 
            ]);
        }

        if ($this->data->get('video'))
        {
            $content = array_merge($content, [
                'video' => [
                    '@type'        => 'VideoObject',
                    'name'         => $this->data->get('title'),
                    'description'  => $this->data->get('description'),
                    'thumbnailUrl' => $this->data->get('image'),
                    'contentUrl'   => $this->data->get('video'),
                    'uploadDate'   => $this->data->get('datePublished')
                ]
            ]);
        }

        // Add author
        $this->addAuthor($content);

        $this->addRating($content);
        $this->addDate($content);

		return $content;
	}

    /**
     *  Constructs the Course Content Type
     *  https://developers.google.com/search/docs/data-types/courses
     *
     *  @return  array
     */
	private function contentTypeCourse()
	{
        $content = [
            '@type' => 'Course',
            'name'  => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'courseCode' => $this->data->get('course_code'),
            'provider' => [
                '@type'  => 'Organization',
                'name'   => $this->data->get('sitename')
            ],
            'hasCourseInstance' => [
                '@type' => 'CourseInstance',
                'name'  => $this->data->get('title'),
                'description'  => $this->data->get('description'),
                'courseMode' => $this->data->get('course_mode'),
                'startDate' => $this->data->get('startDate'),
                'endDate' => $this->data->get('endDate'),
                'location' => [
                    '@type' => 'Place',
                    'name' => $this->data->get('place_name'),
                    'address' => $this->getPostalAddress()
                ],
                'image' => [
                    '@type' => 'ImageObject',
                    'url'   => $this->data->get('image')
                ],
                'performer' => [
                    '@type' => $this->data->get('performer_type'),
                    'name' => $this->data->get('performer_name')
                ],
                'courseWorkload' => $this->data->get('courseWorkload')
            ]            
        ];

        if ($price = $this->data->get('price'))
        {
            $offerCommon = [
                'category' => $this->data->get('offerCategory', 'Free'),
                'url' => $this->data->get('url'),
                'availability' => $this->data->get('availability'),
                'priceCurrency' => $this->data->get('priceCurrency'),
                'validFrom' => $this->data->get('validFrom')
            ];

            if (is_array($price))
            {
                $offer = [
                    '@type' => 'AggregateOffer',
                    'offerCount' => $this->data->get('offerCount', 1),
                    'lowPrice' => $price[0],
                    'highPrice' => $price[1]
                ];
            }
            else 
            {
                $offer = [
                    '@type' => 'Offer',
                    'price' => $price
                ];
            }

            $content['offers'] = array_merge($offer, $offerCommon);
        }
      
        $this->addRating($content);
        $this->addDate($content);

        return $content;
	}

    /**
     *  Constructs the Review Content Type
     *  https://developers.google.com/search/docs/data-types/reviews
     *
     *  @return  array
     */
    private function contentTypeReview()
    {
        $content = [
            '@type' => 'Review',
            'description' => $this->data->get('description'),
            'url' => $this->data->get('url'), 
            'datePublished' => $this->data->get('datePublished'),
            'publisher'  => [
                '@type'  => 'Organization',
                'name'   => $this->data->get('sitename'),
                'sameAs' => $this->data->get('siteurl')
            ],
            'inLanguage' => $this->data->get('language_code'),
            'itemReviewed' => [
                '@type' => $this->data->get('itemReviewedType'),
                'name' => $this->data->get('title'),
                'image' => $this->data->get('image'),
                'sameAs' => $this->data->get('itemReviewedURL')
            ]
        ];
        
        if ($this->data->get('itemReviewedType') == 'LocalBusiness') 
        {
            $content = array_merge_recursive($content, [
                'itemReviewed' => [
                    'address'    => $this->getPostalAddress(),
                    'priceRange' => $this->data->get('priceRange'),
                    'telephone'  => $this->data->get('telephone')
                ]
            ]);
        }

        if (in_array($this->data->get('itemReviewedType'), ['Movie', 'Book']))
        {
            $content = array_merge_recursive($content, [
                'itemReviewed' => [
                    'datePublished' => $this->data->get('itemReviewedPublishedDate')
                ]
            ]);
        }

        if ($this->data->get('itemReviewedType') == 'Movie')
        {
            $movie = [
                'itemReviewed' => [
                    'director' => [
                        '@type' => 'Person',
                        'name'  => $this->data->get('movie_director')
                    ]
                ]
            ]; 

            if ($actors = $this->data->get('actors'))
            {
                foreach ($actors as $key => $actor)
                {
                    if (empty($actor->name))
                    {
                        continue;
                    }

                    $movie['itemReviewed']['actor'][] = [
                        '@type' => 'Person',
                        'name'  => trim($actor->name)
                    ];
                }
            }

            $content = array_merge_recursive($content, $movie);
        }

        if ($this->data->get('itemReviewedType') == 'Book')
        {
            $content = array_merge_recursive($content, [
                'itemReviewed' => [
                    'isbn' => $this->data->get('book_isbn'),
                    'author' => [
                        '@type'  => 'Person',
                        'name'   => $this->data->get('book_author'),
                        'sameAs' => $this->data->get('book_author_url')
                    ]
                ]
            ]);
        }

        // Handle Product Type
        if ($this->data->get('itemReviewedType') == 'Product')
        {
            $content['itemReviewed']['sku'] = $this->data->get('product_sku');
            $content['itemReviewed']['mpn'] = $this->data->get('product_sku');
            $content['itemReviewed']['brand'] = [
                '@type' => 'Brand',
                'name'  => $this->data->get('product_brand')
            ];

            $content['itemReviewed']['description'] = $this->data->get('product_description');

            // Rating
            if ($this->data->get('ratingValue') && $this->data->get('reviewCount'))
            {
                $content['itemReviewed']['aggregateRating'] = [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => $this->data->get('ratingValue'),
                    'reviewCount' => $this->data->get('reviewCount')
                ];
            }

            // offers
            if ($this->data->get('offerprice'))
            {
                $content['itemReviewed']['offers'] = [
                    '@type'           => 'Offer',
                    'priceCurrency'   => $this->data->get('currency', 'USD'),
                    'url'             => $this->data->get('itemReviewedURL'),
                    'itemCondition'   => $this->data->get('condition', 'http://schema.org/NewCondition'),
                    'availability'    => $this->data->get('availability', 'http://schema.org/InStock'),
                    'price'           => $this->data->get('offerprice'),
                    'priceValidUntil' => $this->data->get('pricevaliduntil')
                ];
            }
            
            // Review
            if ($reviews = $this->getReviews())
            {
                $content['itemReviewed']['review'] = $reviews;
            }
        }

        // Add author
        $this->addAuthor($content);

        if ($this->data->get('ratingValue'))
        {
            $content = array_merge($content, [
                'reviewRating' => [
                    '@type'       => 'Rating',
                    'ratingValue' => $this->data->get('ratingValue'),
                    'worstRating' => $this->data->get('worstRating', 0),
                    'bestRating'  => $this->data->get('bestRating', 5)
                ]
            ]);
        }

        return $content;
    }

    /**
     * Returns the reviews of the data
     * 
     * @return  array
     */
    private function getReviews()
    {
        if (!$this->data->get('review') || !$this->data->get('reviewCount'))
        {
            return [];
        }

        $bestRating = $this->data->get('bestRating', 5);
        $worstRating = $this->data->get('worstRating', 0);

        $review_data = [];
        foreach ($this->data->get('review') as $review)
        {
            $rating = $review['rating'];

            $review_data[] = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name'  => $review['author'],
                ],
                'datePublished' => $review['datePublished'],
                'description' => $review['description'],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'bestRating'  => $bestRating,
                    'ratingValue' => $rating,
                    'worstRating' => $worstRating
                ]
            ];
        }

        return $review_data;
    }

    /**
     *  Constructs the Fact Check Content Type
     *  https://developers.google.com/search/docs/data-types/factcheck
     *
     *  @return  array
     */
    private function contentTypeFactCheck()
    {
        $content = [
            '@type' => 'ClaimReview',
            'url' => $this->data->get('factcheckURL'),
            'itemReviewed' => [
                '@type'  => 'CreativeWork',
                'author' => [
                    '@type'  => $this->data->get('claimAuthorType'),
                    'name'   => $this->data->get('claimAuthorName'),
                    'sameAs' => $this->data->get('claimURL')
                ],
                'datePublished' => $this->data->get('claimDatePublished')
            ],
            'claimReviewed' => $this->data->get('title'),
            'author' => [
                '@type' => 'Organization',
                'name'  => $this->data->get('sitename')
            ],
            'reviewRating' => [
                '@type'         => 'Rating',
                'ratingValue'   => $this->data->get('factcheckRating'),
                'bestRating'    => $this->data->get('bestFactcheckRating'),
                'worstRating'   => $this->data->get('worstFactcheckRating'),
                'alternateName' => $this->data->get('alternateName')
            ]
        ];

        return $this->addDate($content);
    }

    /**
     *  Constructs the Service Content Type
     *  https://schema.org/Service
     *
     *  @return  array
     */
    private function contentTypeService()
    {
        $content = [
            '@type' => 'Service',
            'name' => $this->data->get('title'),
            'serviceType' => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'image' => $this->data->get('image'),
            'url' => $this->data->get('url'), 
            'provider' => [
                '@type'  => $this->data->get('provider_type'),
                'name' => $this->data->get('provider_name'),
                'image' => $this->data->get('provider_image'),
                'telephone' => $this->data->get('phone'),
                'address' => $this->getPostalAddress()
            ]
        ];

        // Offer / Pricing
        if ((float) $this->data->get('offerPrice') > 0)
        {
            $content = array_merge($content, [
                'offers' => [
                    '@type'         => 'Offer',
                    'priceCurrency' => $this->data->get('currency', 'USD'),
                    'price'         => $this->data->get('offerPrice')
                ]
            ]);
        }

        return $content;
    }

    /**
     *  Constructs the Video Content Type
     *  https://developers.google.com/search/docs/data-types/videos
     *
     *  @return  array
     */
    private function contentTypeVideo()
    {
        if (empty($this->data->get('contentUrl')) && empty($this->data->get('embedUrl')))
        {
            return;
        }
        
        return [
            '@type'        => 'VideoObject',
            'name'         => $this->data->get('name'),
            'description'  => $this->data->get('description'),
            'thumbnailUrl' => $this->data->get('thumbnailUrl'),
            'uploadDate'   => $this->data->get('uploadDate'),
            'contentUrl'   => $this->data->get('contentUrl'),
            'embedURL'     => $this->data->get('embedUrl'),
            'transcript'   => $this->data->get('transcript')
        ];
    }

    /**
     * Generates the Job Posting Content Type
     * https://developers.google.com/search/docs/data-types/job-posting
     *
     * @return void
     */
    private function contentTypeJobPosting()
    {
        $json = [
            '@type' => 'JobPosting',
            'title' => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'datePosted' => $this->data->get('datePublished'),
            'educationRequirements' => $this->data->get('educationRequirements'),
            'employmentType' => $this->data->get('employmenttype'),
            'industry' => $this->data->get('industry'),
            'jobLocation' => [
                '@type' => 'Place',
                'address' => $this->getPostalAddress()
            ],
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $this->data->get('hiring_oprganization_name'),
                'sameAs' => $this->data->get('hiring_oprganization_url'),
                'logo' => $this->data->get('hiring_organization_logo')
            ],
            'validThrough' => $this->data->get('valid_through')
        ];

        $salary = $this->data->get('salary');

        if ($salary > 0)
        {
            if (is_array($salary) && count($salary) > 1)
            {
                $salary_value = [
                    'value' => trim($salary[0]),
                    'minValue' => trim($salary[0]),
                    'maxValue' => trim($salary[1])
                ];
            } else
            {
                $salary_value = ['value' => $salary];
            }

            $json = array_merge($json, [
                'baseSalary' => [
                    '@type' => 'MonetaryAmount',
                    'currency' => $this->data->get('currency'),
                    'value' => [
                        '@type' => 'QuantitativeValue',
                        'unitText' => $this->data->get('salary_unit')
                    ]
                ],
            ]);

            $json = array_merge_recursive($json, [
                'baseSalary' => [
                    'value' => $salary_value
                ]
            ]);
        }

        return $json;
    }

    /**
     *  Constructs the Custom Code Content Type
     *
     *  @return  string    The custom code entered by user
     */
    private function contentTypeCustom_Code()
    {
        return $this->data->get('custom_code', '');
    }
    
    
    /**
     *  Appends the aggregateRating property to object
     *
     *  @param  array  &$content
     */
    private function addRating(&$content)
    {
        if (!$this->data->get('ratingValue') || !$this->data->get('reviewCount'))
        {
            return;
        }

        return $content = array_merge($content, [
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => $this->data->get('ratingValue'),
                'reviewCount' => $this->data->get('reviewCount'),
                'worstRating' => $this->data->get('worstRating', 0),
                'bestRating'  => $this->data->get('bestRating', 5)
            ]
        ]);
    }

    /**
     * Returns the PostalAddress type used in most of the content types
     *
     * @return array
     */
    private function getPostalAddress()
    {
        return [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $this->data->get('streetAddress'),
            'addressCountry'  => $this->data->get('addressCountry'),
            'addressLocality' => $this->data->get('addressLocality'),
            'addressRegion'   => $this->data->get('addressRegion'),
            'postalCode'      => $this->data->get('postalCode')
        ];
    }

    /**
     *  Appends date properties to object
     *
     *  @param  array  &$content
     */
    private function addDate(&$content)
    {
        return $content = array_merge($content, [
            'datePublished' => $this->data->get('datePublished'),
            'dateCreated'   => $this->data->get('dateCreated'),
            'dateModified'  => $this->data->get('dateModified')
        ]);
    }

    /**
     * Adds the author property to the content.
     * 
     * @param   array  &$content
     * 
     * @return  void
     */
    private function addAuthor(&$content)
    {
        if ($this->data->get('authorName'))
        {
            $content = array_merge($content, [
                'author' => [
                    '@type' => $this->data->get('authorType'),
                    'name'  => $this->data->get('authorName'),
                    'url'   => $this->data->get('authorUrl')
                ]
            ]);
        }
    }
}