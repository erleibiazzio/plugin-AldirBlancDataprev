<?php

namespace AldirBlancDataprev;

use MapasCulturais\App;
use MapasCulturais\Entities\Registration;

class Plugin extends \AldirBlanc\PluginValidador
{
    function __construct(array $config = [])
    {
        $config += [
            // se true, só exporta as inscrições pendentes que já tenham alguma avaliação
            'exportador_requer_homologacao' => env('DATAPREV_REQUER_HOMOLOGACAO', true),
            
            'csv_inciso1' => require_once env('AB_CSV_INCISO1', __DIR__ . '/config-csv-inciso1.php'),
            'csv_inciso2' => require_once env('AB_CSV_INCISO2', __DIR__ . '/config-csv-inciso2.php'),
        ];

        parent::__construct($config);
    }

    function _init()
    {
        $app = App::i();

        $plugin = $app->plugins['AldirBlanc'];

        //botao de export csv
        $app->hook('template(opportunity.single.header-inscritos):end', function () use($plugin, $app){
            $inciso1Ids = [$plugin->config['inciso1_opportunity_id']];
            $inciso2Ids = array_values($plugin->config['inciso2_opportunity_ids']);
            $opportunities_ids = array_merge($inciso1Ids, $inciso2Ids);
            $requestedOpportunity = $this->controller->requestedEntity; //Tive que chamar o controller para poder requisitar a entity
            $opportunity = $requestedOpportunity->id;
            if(($requestedOpportunity->canUser('@control')) && in_array($requestedOpportunity->id,$opportunities_ids) ) {
                $app->view->enqueueScript('app', 'aldirblanc', 'aldirblanc/app.js');
                if (in_array($requestedOpportunity->id, $inciso1Ids)){
                    $inciso = 1;
                }
                else if (in_array($requestedOpportunity->id, $inciso2Ids)){
                    $inciso = 2;
                }
                $this->part('aldirblanc/csv-button', ['inciso' => $inciso, 'opportunity' => $opportunity]);
            }
        });
        

        // uploads de CSVs 
        $app->hook('template(opportunity.<<single|edit>>.sidebar-right):end', function () {
            $opportunity = $this->controller->requestedEntity; 
            if($opportunity->canUser('@control')){
                $this->part('aldirblanc/dataprev-uploads', ['entity' => $opportunity]);
            }
        });

        parent::_init();
    }

    function register()
    {
        $app = App::i();

        $this->registerOpportunityMetadata('dataprev_processed_files', [
            'label' => 'Arquivos do Dataprev Processados',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $this->registerRegistrationMetadata('dataprev_filename', [
            'label' => 'Nome do arquivo de retorno do dataprev',
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerRegistrationMetadata('dataprev_raw', [
            'label' => 'Dataprev raw data (csv row)',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $this->registerRegistrationMetadata('dataprev_processed', [
            'label' => 'Dataprev processed data',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $file_group_definition = new \MapasCulturais\Definitions\FileGroup('dataprev', ['^text/csv$'], 'O arquivo enviado não é um csv.',false,null,true);
        $app->registerFileGroup('opportunity', $file_group_definition);

        parent::register();
    }

    function getName(): string
    {
        return 'Dataprev';
    }

    function getSlug(): string
    {
        return 'dataprev';
    }

    function getControllerClassname(): string
    {
        return Controller::class;
    }

    function isRegistrationEligible(Registration $registration): bool
    {
        return true;
    }
}
