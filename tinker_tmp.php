$t = App\Models\RepairTicket::with("items")->where("ticket_number","REP-2026-0002")->first();
echo "labor_cost: ".$t->labor_cost."\n";
echo "parts_cost: ".$t->parts_cost."\n";
echo "discount_amount: ".$t->discount_amount."\n";
echo "discount_validated: ".($t->discount_validated ? "true" : "false")."\n";
echo "total_cost: ".$t->total_cost."\n";
foreach($t->items as $i){ echo "qty:".$i->quantity." up:".$i->unit_price." disc:".$i->discount_amount." tot:".$i->total."\n"; }
