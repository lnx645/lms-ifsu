<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function tahunAjaran() {
        return response()->json(TahunAjaran::select("id","nama")->where("active",true)->orderBy("nama")->get());
    }
    public function jurusan() {
        return response()->json(Jurusan::select("id","nama","kode")->orderBy("nama")->get());
    }
    public function kelas(Request $r) {
        $q = Kelas::with("jurusan:id,nama");
        if ($r->tahun_ajaran_id) $q->where("tahun_ajaran_id", $r->tahun_ajaran_id);
        if ($r->jurusan_id) $q->where("jurusan_id", $r->jurusan_id);
        return response()->json($q->orderBy("nama")->get());
    }
    public function mataPelajaran() {
        return response()->json(Matpel::select("id","nama","kode")->orderBy("nama")->get());
    }
    public function guru() {
        return response()->json(Guru::select("id","nama_lengkap","nip")->orderBy("nama_lengkap")->get());
    }
    public function siswa(Request $r) {
        $q = Siswa::query();
        if ($r->jurusan_id) $q->where("jurusan_id", $r->jurusan_id);
        return response()->json($q->orderBy("nama_lengkap")->paginate(100));
    }
    public function siswaByKelas($id, Request $r) {
        $tahunAjaranId = $r->tahun_ajaran_id ?? TahunAjaran::where("active", true)->value("id");
        $data = SiswaKelas::with("siswa:nisn,nama_lengkap")
            ->where("kelas_id", $id)
            ->where("tahun_ajaran_id", $tahunAjaranId)
            ->where("active", true)
            ->get()
            ->map(fn($sk) => ["nisn" => $sk->siswa_nisn, "nama" => $sk->siswa->nama_lengkap ?? null]);
        return response()->json($data);
    }
}
