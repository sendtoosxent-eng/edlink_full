<?php
namespace App\Livewire;
use App\Models\User;use Illuminate\Support\Facades\Auth;use Livewire\Attributes\Layout;use Livewire\Component;use Livewire\WithPagination;
#[Layout('layouts.app')] class ParentsIndex extends Component {use WithPagination;public string $search='';public function updatingSearch():void{$this->resetPage();}public function render(){$parents=User::with('portalStudents.schoolClass')->where('school_id',Auth::user()->school_id)->where('role','parent')->when($this->search,fn($q)=>$q->where(fn($q)=>$q->where('name','like','%'.$this->search.'%')->orWhere('email','like','%'.$this->search.'%')->orWhere('phone','like','%'.$this->search.'%')))->orderBy('name')->paginate(15);return view('livewire.parents-index',['parents'=>$parents,'pageTitle'=>'All Parents']);}}
