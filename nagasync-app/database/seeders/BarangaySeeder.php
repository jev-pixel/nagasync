<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Barangay;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = ['Abella','Bagumbayan Norte','Bagumbayan Sur','Balatas','Calauag','Cararayan',
        'Carolina','Concepcion Grande','Concepcion Pequeña','Dayangdang','Del Rosario','Dinaga',
        'Igualdad Interior','Lerma','Liboton','Mabolo','Pacol','Panicuason','Peñafrancia','Sabang',
        'San Felipe','San Francisco','San Isidro','Santa Cruz','Tabuco','Tinago','Triangulo'];
        foreach ($names as $n) { Barangay::firstOrCreate(['name' => $n]); }
    }
}
