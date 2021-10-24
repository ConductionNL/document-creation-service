<?php

namespace App\Service;

use App\Entity\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class TemplateService
{
    /**
     * @param string $entityName
     * @return Entity|array
     */

    public function render(Template $template): ?string
    {
        $request = NEW Request();
        $variables = $this->getVariables($request);
        $contentType = $request->headers->get('Accept','application/pdf');

        $date = new \DateTime();
        $response = New Response();
        $response->headers->set('content-Type',$contentType);

        switch ($contentType) {
            case 'application/ld+json':
            case 'application/json':
            case 'application/hal+json':
            case 'application/xml':
                return $result;
            case 'application/vnd.ms-word':
            case 'vnd.openxmlformats-officedocument.wordprocessing':
                $extension = 'docx';
                $file= $this->renderWord($template);
                $response->setContent($file);
                break;
            case 'application/pdf':
                $extension = 'pdf';
                $file = $this->renderPdf($template);
                $response->setContent($file);
                break;
            default;
                /* @todo throw error */
                break;
        }

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "{$template->getName()}_{$date}.{$extension}");
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    public function renderWord(Template $template): ?string
    {
        $stamp = microtime();
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $this->getContent($template));
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

        $filename = dirname(__FILE__, 3)."/var/{$template->getTemplateName()}_{$stamp}.docx";
        $objWriter->save($filename);

        return $filename;
    }


    public function renderPdf(Template $template): ?string
    {
        /* ingewikkeld
        $stamp = microtime();
        // We start with a word file
        $filename = $this->renderWord($template);

        // And turn that into an pdf
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filename);
        // Unset the orignal file
        unlink($filename); // deletes the temporary file

        $rendererName = Settings::PDF_RENDERER_DOMPDF;
        $rendererLibraryPath = realpath('../vendor/dompdf/dompdf');
        Settings::setPdfRenderer($rendererName, $rendererLibraryPath);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');


        $filename = dirname(__FILE__, 3)."/var/{$template->getTemplateName()}_{$stamp}.pdf";
        $objWriter->save($objWriter);
        */

        // Simpel
        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->getContent($template));
        $dompdf->render();

        return $dompdf->output();
    }

    public function getVariables(Request $request): ?string
    {
        $query = $request->query->all();

        // @todo we want to support both json and xml here */
        $body = json_decode($request->getContent(), true);

        $variables = array_merge($query, $body);

        return $variables;
    }

    public function getContent(Template $template, array $variables): ?string
    {
        switch ($template->getTemplateEngine()) {
            case 'twig':
                $template = $this->templating->createTemplate($template->getContent());
                return $template->render($variables);
                break;
            case 'md':
                return $template->getContent();
            case 'rt':
                return $template->getContent();
            default;
                /* @todo throw error */
                break;
        }
    }


}
