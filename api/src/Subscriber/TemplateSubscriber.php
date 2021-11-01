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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig_Environment as Environment;


class TemplateSubscriber implements EventSubscriberInterface
{
    private TemplateService         $templateService;
    private EntityManagerInterface  $entityManager;

    public function __construct(TemplateService $templateService, EntityManagerInterface $entityManager)
    {
        $this->templateService = $templateService;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['template', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function template(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');
        $id = $event->getRequest()->attributes->get('id');
        $type = $event->getRequest()->attributes->get('type');
        $template = $this->entityManager->getRepository("App:Template")->findOneBy(['id'=>$id]);

        if ($route != 'api_templates_render_collection' || $method != 'POST') {
            return;
        }

        if ($template == null || !$template instanceof Template) {
            throw new NotFoundHttpException('Unable to find template');
        }

        $event->setResponse($this->templateService->render($template, $type));
        return $event;
    }
}
