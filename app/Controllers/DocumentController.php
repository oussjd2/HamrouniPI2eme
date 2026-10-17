<?php
namespace App\Controllers;
use App\Core\{Auth,Validation as V,HttpException};
use App\Models\Location;
use Dompdf\{Dompdf,Options};

final class DocumentController {
    public static function html(array $row, string $type): string {
        if (!in_array($type,['contrat','facture','recu'],true)) { throw new HttpException(404,'Document inconnu.'); }
        if (!in_array($row['statut'],['confirmee','en_cours','retour_a_controler','terminee'],true) || !$row['snapshot']) { throw new HttpException(403,'Documents disponibles pour les locations confirmées et non annulées.'); }
        if ($type==='recu' && !$row['date_paiement']) { throw new HttpException(403,'Le reçu est disponible après enregistrement du paiement.'); }
        $snapshot=json_decode($row['snapshot'],true,512,JSON_THROW_ON_ERROR);
        $row=array_replace($row,$snapshot);
        $title=['contrat'=>'Contrat de location','facture'=>'Facture de location','recu'=>'Reçu de paiement'][$type];
        ob_start(); require ROOT . '/app/Views/documents/pdf.php'; return ob_get_clean();
    }
    public function download(): void {
        $actor=Auth::requireRoles(['client','agent','inventaire']);
        $row=Location::accessible(V::integer($_GET,'id','Location',1),$actor);
        $type=is_string($_GET['type'] ?? null) ? $_GET['type'] : '';
        $html=self::html($row,$type);
        $options=new Options(); $options->set('isRemoteEnabled',false); $options->set('isPhpEnabled',false); $options->set('defaultFont','DejaVu Sans'); $options->set('chroot',ROOT . '/storage');
        $pdf=new Dompdf($options); $pdf->loadHtml($html,'UTF-8'); $pdf->setPaper('A4'); $pdf->render();
        $pdf->stream($type . '-location-' . $row['id'] . '.pdf',['Attachment'=>true]);
    }
}
