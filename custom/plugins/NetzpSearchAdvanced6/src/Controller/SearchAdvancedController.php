<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Controller;

use NetzpSearchAdvanced6\Core\Content\SearchLog\SearchLogCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['administration']])]
class SearchAdvancedController extends AbstractController
{
    #[Route(path: '/admin/netzp/searchadvanced/export', defaults: ['auth_required' => false], name: 'admin.netzp.searchadvanced.export', options: ['seo' => false], methods: ['GET'])]
    public function export(Request $request, Context $context)
    {
        $repo = $this->container->get('s_plugin_netzp_search_log.repository');
        $criteria = new Criteria();
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('language');

        $criteria->addSorting(new FieldSorting('createdAt', 'DESC'));

        $logEntries = $repo->search($criteria, $context)->getEntities();

       return $this->exportCsv($logEntries);
    }

    private function exportCsv(SearchLogCollection $logEntries)
    {
        $list = [
            ['Query', 'Hits', 'Origin', 'SalesChannel', 'Language', 'Date'],
        ];

        foreach($logEntries as $logEntry)
        {
            $query = $logEntry->getQuery();
            $hits = $logEntry->getHits();
            $origin = $logEntry->getOrigin();
            $salesChannel = $logEntry->getSalesChannel() ? $logEntry->getSalesChannel()->getName() : '';
            $language = $logEntry->getLanguage() ? $logEntry->getLanguage()->getName() : '';
            $date = $logEntry->getCreatedAt();
            $list[] = [
                $query, $hits, $origin, $salesChannel, $language, $date->format('Y-m-d H:i')
            ];
        }

        $s = '';
        foreach ($list as $line)
        {
            $s .= implode(';', $line) . PHP_EOL;
        }

        $response = new Response();
        $fileName = 'searchlog.csv';

        $response->setContent($s);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Symfony-Session-NoAutoCacheControl', '1');

        return $response;
    }
}
