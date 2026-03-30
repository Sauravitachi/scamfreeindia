<?php

namespace Database\Seeders;

use App\Models\AppUiData;
use Illuminate\Database\Seeder;

class AppUiDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sections = [
            [
                'name' => 'hero_section',
                'data' => [
                    'hero_section_title' => 'Protecting You from Scams',
                    'hero_section_video' => null,
                    'hero_section_description' => 'Scam Free India is a platform designed to empower citizens against digital frauds and scams.',
                    'hero_section_button_text' => 'Get Started',
                    'hero_section_button_url' => '/register',
                ]
            ],
            [
                'name' => 'video_section',
                'data' => [
                    'video_section' => [
                        'cards' => [
                            [
                                'title' => 'How it Works',
                                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                                'description' => 'A brief overview of how our platform helps you report and resolve scams.',
                                'image_url' => null,
                                'pdf_url' => null,
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'services_cards',
                'data' => [
                    'services_cards' => [
                        [
                            'image_url' => null,
                            'heading' => 'Scam Reporting',
                            'sub_heading' => 'Efficiently report scams to authorities.'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'review_section_cards',
                'data' => [
                    'review_section_cards' => [
                        [
                            'image_url_1' => null,
                            'name' => 'Aravind Swamy',
                            'detail' => 'Great platform that helped me recover my lost funds.',
                            'review_link' => '#',
                            'review_rating' => 5,
                        ]
                    ]
                ]
            ],
            [
                'name' => 'faq_section_cards',
                'data' => [
                    'faq_section_cards' => [
                        [
                            'question' => 'How do I report a scam?',
                            'answer' => 'You can report a scam by clicking on the "Report Now" button and filling out the form.'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'seo_meta',
                'data' => [
                    'meta_title' => 'Scam Free India - Empowering Citizens Against Fraud',
                    'meta_description' => 'Report frauds, learn about safety, and get help from experts.',
                    'og_image' => null,
                ]
            ]
        ];

        foreach ($sections as $section) {
            AppUiData::updateOrCreate(
                ['name' => $section['name']],
                ['data' => $section['data']]
            );
        }
    }
}
