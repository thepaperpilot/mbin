<?php declare(strict_types=1);

namespace App\MessageHandler\ActivityPub;

use App\Message\ActivityPub\CreateActorMessage;
use App\Service\ActivityPubManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateActorHandler implements MessageHandlerInterface
{
    public function __construct(private ActivityPubManager $activityPubManager)
    {
    }

    public function __invoke(CreateActorMessage $message): void
    {
        $this->activityPubManager->findActorOrCreate($message->handle);
    }
}