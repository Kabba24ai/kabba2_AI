<?php

namespace App\Enums\Service;

enum ServiceMediaCategory: string
{
    case ComplaintEvidence           = 'complaint_evidence';
    case Note                        = 'note';
    case BeforeRepair                = 'before_repair';
    case DuringRepair                = 'during_repair';
    case AfterRepair                 = 'after_repair';
    case WarrantyDocumentation       = 'warranty_documentation';
    case CustomerDamageDocumentation = 'customer_damage_documentation';
    case General                     = 'general';

    public function label(): string
    {
        return match ($this) {
            self::ComplaintEvidence           => 'Complaint Evidence',
            self::Note                        => 'Note',
            self::BeforeRepair                => 'Before Repair',
            self::DuringRepair                => 'During Repair',
            self::AfterRepair                 => 'After Repair',
            self::WarrantyDocumentation       => 'Warranty Documentation',
            self::CustomerDamageDocumentation => 'Customer Damage Documentation',
            self::General                     => 'General',
        };
    }

    /** The ticket section this category belongs to — drives storage folder and default retention class. */
    public function workflowStage(): ServiceMediaWorkflowStage
    {
        return match ($this) {
            self::ComplaintEvidence, self::CustomerDamageDocumentation => ServiceMediaWorkflowStage::Complaint,
            self::Note                                                 => ServiceMediaWorkflowStage::Notes,
            self::BeforeRepair, self::DuringRepair, self::AfterRepair  => ServiceMediaWorkflowStage::Repair,
            self::WarrantyDocumentation                                => ServiceMediaWorkflowStage::Warranty,
            self::General                                              => ServiceMediaWorkflowStage::General,
        };
    }
}
