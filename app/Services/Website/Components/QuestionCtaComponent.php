<?php

namespace App\Services\Website\Components;

class QuestionCtaComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'question_cta'; }
    public function displayName(): string { return 'Question CTA'; }
    public function icon(): string        { return 'heroicon-o-chat-bubble-left-ellipsis'; }
    public function description(): string { return 'Full-width CTA strip: icon, heading, description, button, and phone number.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._question_cta'; }

    public function viewData(array $context): array
    {
        return ['section' => $this->section($context)];
    }

    public function defaultData(): array
    {
        return [
            'section' => [
                'title'       => 'Have a question?',
                'subtitle'    => 'Our team is ready to help you find the right equipment for your project.',
                'button_text' => 'Call Main Sales Line',
                'button_url'  => '',
                'content'     => [
                    'icon'         => 'heroicon-o-chat-bubble-left-ellipsis',
                    'phone_number' => '',
                    'status'       => 'Active',
                ],
            ],
            'items' => [],
        ];
    }
}
