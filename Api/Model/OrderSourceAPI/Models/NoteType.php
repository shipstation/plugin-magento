<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * Class NoteType
 * @description The standardized type associated with a note
 */
enum NoteType: string
{
    case BackOrderMessage = 'BackOrderMessage';
    case ConditionNote = 'ConditionNote';
    case GiftMessage = 'GiftMessage';
    case InternalNotes = 'InternalNotes';
    case InStockMessage = 'InStockMessage';
    case MPN = 'MPN';
    case NotesFromBuyer = 'NotesFromBuyer';
    case NotesToBuyer = 'NotesToBuyer';
    case Other = 'Other';
    case OutOfStockMessage = 'OutOfStockMessage';
    case Reason = 'Reason';
    case SpecialInstructions = 'SpecialInstructions';
    case WarningLabel = 'WarningLabel';
    case FeedbackMessage = 'FeedbackMessage';
}
