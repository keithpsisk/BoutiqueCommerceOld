<?php
declare(strict_types=1);

namespace It_All\BoutiqueCommerce\Src\Domain;

/**
 * navigation for admin pages
 */
class NavAdmin
{
    private $sections;

    function __construct()
    {
        $this->setSections();
        return $this;
    }

    private function setSections()
    {
        global $config;

        $this->sections = [
            'Admins' => [
                'link' => '/'.$config['dirs']['admin'].'/admins',
                'subSection' => [
                    'Insert' => [
                        'link' => '/'.$config['dirs']['admin'].'/admins/insert'
                    ]
                ]
            ]
        ];
    }

    public function getSections()
    {
        return $this->sections;
    }
}