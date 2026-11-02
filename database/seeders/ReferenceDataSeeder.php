<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

/**
 * Systemkategorien und CH-Vorlagen. Idempotent; läuft bei jedem Deployment.
 * Die Werte sind Startwerte und müssen im konkreten Vertrag geprüft werden.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'krankenkasse', 'name' => 'Krankenkasse', 'icon' => 'heart', 'tracks_usage' => false, 'sort' => 10],
            ['slug' => 'versicherung', 'name' => 'Versicherung', 'icon' => 'shield', 'tracks_usage' => false, 'sort' => 20],
            ['slug' => 'fitness', 'name' => 'Fitness & Sport', 'icon' => 'dumbbell', 'tracks_usage' => true, 'sort' => 30],
            ['slug' => 'telekom', 'name' => 'Handy & Internet', 'icon' => 'phone', 'tracks_usage' => false, 'sort' => 40],
            ['slug' => 'streaming', 'name' => 'Streaming & Medien', 'icon' => 'play', 'tracks_usage' => true, 'sort' => 50],
            ['slug' => 'software', 'name' => 'Software & Apps', 'icon' => 'app', 'tracks_usage' => true, 'sort' => 60],
            ['slug' => 'wohnen', 'name' => 'Wohnen & Energie', 'icon' => 'home', 'tracks_usage' => false, 'sort' => 70],
            ['slug' => 'mobilitaet', 'name' => 'Mobilität', 'icon' => 'car', 'tracks_usage' => false, 'sort' => 80],
            ['slug' => 'sonstiges', 'name' => 'Sonstiges', 'icon' => 'folder', 'tracks_usage' => false, 'sort' => 999],
        ];

        $ids = [];
        foreach ($categories as $data) {
            $category = Category::updateOrCreate(['user_id' => null, 'slug' => $data['slug']], $data);
            $ids[$data['slug']] = $category->id;
        }

        $templates = [
            [
                'slug' => 'kvg-grundversicherung',
                'name' => 'Krankenkasse Grundversicherung',
                'category' => 'krankenkasse',
                'defaults' => ['notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'], 'notice_value' => 1, 'notice_unit' => 'months', 'cost_interval' => 'monthly', 'send_buffer_days' => 5],
                'help_text' => 'Kündigung auf 31.12.; muss bis 30.11. (bzw. letzter Arbeitstag im November) bei der Kasse eingetroffen sein. Zählt der Eingang, nicht der Poststempel. Offene Prämien vorher begleichen.',
                'compare_url' => 'https://www.priminfo.admin.ch',
                'sort' => 10,
            ],
            [
                'slug' => 'kvg-franchise-300',
                'name' => 'Grundversicherung, Standardmodell mit Franchise 300',
                'category' => 'krankenkasse',
                'defaults' => ['notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['06-30:3', '12-31'], 'notice_value' => 1, 'notice_unit' => 'months', 'cost_interval' => 'monthly', 'send_buffer_days' => 5],
                'help_text' => 'Nur Standardmodell mit freier Arztwahl und tiefster Franchise. Zusätzlich auf 30.6. kündbar mit 3 Monaten Frist (Eingang bis 31.3.); auf 31.12. mit 1 Monat (Eingang bis 30.11.).',
                'compare_url' => 'https://www.priminfo.admin.ch',
                'sort' => 11,
            ],
            [
                'slug' => 'kvg-zusatz',
                'name' => 'Krankenkasse Zusatzversicherung',
                'category' => 'krankenkasse',
                'defaults' => ['notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'], 'notice_value' => 3, 'notice_unit' => 'months', 'cost_interval' => 'monthly', 'send_buffer_days' => 5],
                'help_text' => 'Meist auf Jahresende mit 3 Monaten Frist (Eingang bis 30.9.). Mindestlaufzeit in der Police prüfen. Neue Zusatzversicherung erst abschliessen, dann alte kündigen.',
                'sort' => 12,
            ],
            [
                'slug' => 'vvg-sachversicherung',
                'name' => 'Sach- / Haftpflichtversicherung',
                'category' => 'versicherung',
                'defaults' => ['notice_anchor' => 'term_end', 'min_term_months' => 36, 'renewal_months' => 12, 'notice_value' => 3, 'notice_unit' => 'months', 'cost_interval' => 'yearly', 'send_buffer_days' => 5],
                'help_text' => 'Hausrat, Privathaftpflicht, Auto. Laufzeit und Frist aus der Police übernehmen; gemäss revidiertem VVG meist auf Ende des 3. Jahres kündbar, danach jährlich, mit 3 Monaten Frist.',
                'sort' => 20,
            ],
            [
                'slug' => 'fitness-abo',
                'name' => 'Fitnessabo',
                'category' => 'fitness',
                'defaults' => ['notice_anchor' => 'term_end', 'min_term_months' => 12, 'renewal_months' => 12, 'notice_value' => 2, 'notice_unit' => 'months', 'cost_interval' => 'monthly', 'send_buffer_days' => 5],
                'help_text' => 'Viele Abos verlängern sich automatisch um ein Jahr. Frist und Form (schriftlich, am Empfang, online) im Vertrag prüfen.',
                'sort' => 30,
            ],
            [
                'slug' => 'telekom-abo',
                'name' => 'Handy / Internet',
                'category' => 'telekom',
                'defaults' => ['notice_anchor' => 'term_end', 'min_term_months' => 12, 'renewal_months' => 1, 'notice_value' => 2, 'notice_unit' => 'months', 'cost_interval' => 'monthly', 'send_buffer_days' => 5],
                'help_text' => 'Nach der Mindestlaufzeit oft monatlich kündbar. Bei Rufnummer-Portierung nicht selbst kündigen, der neue Anbieter erledigt das.',
                'sort' => 40,
            ],
            [
                'slug' => 'streaming',
                'name' => 'Streaming / Software-Abo',
                'category' => 'streaming',
                'defaults' => ['notice_anchor' => 'term_end', 'min_term_months' => 1, 'renewal_months' => 1, 'notice_value' => 0, 'notice_unit' => 'days', 'cost_interval' => 'monthly', 'send_buffer_days' => 1],
                'help_text' => 'Meist bis zum nächsten Abrechnungstag im Konto kündbar. Als Vertragsbeginn das Datum der ersten Abbuchung erfassen.',
                'sort' => 50,
            ],
            [
                'slug' => 'jahresabo',
                'name' => 'Jahresabo (Zeitung, Verein, Software)',
                'category' => 'software',
                'defaults' => ['notice_anchor' => 'term_end', 'min_term_months' => 12, 'renewal_months' => 12, 'notice_value' => 1, 'notice_unit' => 'months', 'cost_interval' => 'yearly', 'send_buffer_days' => 3],
                'help_text' => 'Verlängert sich meist stillschweigend um ein Jahr.',
                'sort' => 60,
            ],
            [
                'slug' => 'eigener-vertrag',
                'name' => 'Eigener Vertrag',
                'category' => 'sonstiges',
                'defaults' => ['notice_anchor' => 'term_end', 'min_term_months' => 12, 'renewal_months' => 12, 'notice_value' => 3, 'notice_unit' => 'months', 'cost_interval' => 'monthly', 'send_buffer_days' => 5],
                'help_text' => 'Alle Werte frei anpassen.',
                'sort' => 999,
            ],
        ];

        foreach ($templates as $t) {
            ContractTemplate::updateOrCreate(['slug' => $t['slug']], [
                'name' => $t['name'],
                'category_id' => $ids[$t['category']] ?? null,
                'defaults' => $t['defaults'],
                'help_text' => $t['help_text'],
                'compare_url' => $t['compare_url'] ?? null,
                'sort' => $t['sort'],
            ]);
        }
    }
}
