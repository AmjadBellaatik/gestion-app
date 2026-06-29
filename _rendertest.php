use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Documents\DocumentPlaceholderService;

$doc = Document::whereHas('documentType', fn($q)=>$q->whereIn('code',[DocumentType::INVOICE, DocumentType::QUOTATION]))
    ->whereHas('items')->with(['items','company','client','reseller','documentType','sale'])->latest('id')->first();
if(!$doc){ echo "NO_DOC_WITH_ITEMS\n"; return; }
echo "doc #{$doc->id} type={$doc->documentType->code} items=".$doc->items->count()." reseller=".($doc->reseller?->name ?? '(client)')."\n";
$view = $doc->documentType->code === DocumentType::QUOTATION ? 'documents.pdf.commercial-quotation' : 'documents.pdf.commercial-invoice';
try {
  $html = view($view, [
    'document'=>$doc,'company'=>$doc->company,'template'=>$doc->template,
    'client'=>$doc->client,'supplier'=>$doc->supplier,
    'motorcycleUnit'=>$doc->primaryMotorcycleUnit(),
    'qrSvg'=>'','placeholders'=>DocumentPlaceholderService::context($doc),
  ])->render();
  echo "RENDER_OK length=".strlen($html)."\n";
  echo "header 'N°': ".(substr_count($html,'>N°<') > 0 ? 'YES':'no')."\n";
  echo "header 'Prix unitaire HT': ".(str_contains($html,'Prix unitaire HT')?'YES':'no')."\n";
  echo "still shows 'Prix unitaire TTC': ".(str_contains($html,'Prix unitaire TTC')?'YES(bad)':'no(good)')."\n";
  preg_match_all('#<td class="num">(\d+)</td>#u',$html,$m);
  echo "numeration cells: [".implode(', ', array_slice($m[1],0,6))."]\n";
} catch (\Throwable $e){ echo "RENDER_ERROR: ".$e->getMessage()."\n"; }
