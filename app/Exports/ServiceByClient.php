<?php

namespace App\Exports;

use App\Group;
use App\ClientService;
use App\User;
use App\ClientTransaction;
use App\Service;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;


use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use DB, Response;
use DateTime;

class ServiceByClient implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(array $data)
  {
      $this->data = $data;
  }

  public function registerEvents(): array
  {

      return [

          BeforeExport::class => function(BeforeExport $event) {
            $event->writer->getProperties()->setCreator('4ways')
                ->setTitle("Transaction History")
                ->setSubject("Office 2007 XLSX Test Document");

          },

          AfterSheet::class => function(AfterSheet $event) {

              $sheet = $event->sheet->getDelegate();
              $sheet->getColumnDimension('A')->setAutoSize(false);
              $sheet->getColumnDimension('A')->setWidth(25);
          },
      ];
  }

  public function view(): View
  {

      return view('export.client_services', [
          'userdata' => $this->data,
      ]);
  }



}
