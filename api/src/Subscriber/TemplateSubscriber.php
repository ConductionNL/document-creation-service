<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Template;
use App\Service\TemplateService;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig_Environment as Environment;

class TemplateSubscriber implements EventSubscriberInterface
{
    private $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['template', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function template(ViewEvent $event)
    {
        $result = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');


        if (!$result instanceof Template || $route != 'api_templates_render_template_item' || $method != 'POST') {
            return;
        }

        return $this->templateService->render($result);
    }
}
