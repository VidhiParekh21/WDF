let announcements=[];
let filteredAnnouncements=[];
let currentPage=1;
const announcementsPerPage=5;

const container=document.getElementById("announcementContainer");
const searchInput=document.getElementById("searchAnnouncement");
const categoryFilter=document.getElementById("categoryFilter");
const sortSelect=document.getElementById("sortAnnouncements");
const loading=document.getElementById("announcementLoading");
const errorMessage=document.getElementById("announcementError");
const pagination=document.getElementById("announcementPagination");
const noResults=document.getElementById("noResults");

async function loadAnnouncements(){
    try{
        const response=await fetch("announcements.json");
        if(!response.ok) throw new Error("Unable to load announcements.json");
        announcements=await response.json();
        filteredAnnouncements=[...announcements];
        createCategoryFilter();
        sortAnnouncements();
        renderAnnouncements();
    }catch(error){
        console.error(error);
        loading.style.display="none";
        errorMessage.style.display="block";
        errorMessage.innerHTML="<strong>Unable to load announcements.</strong><br><br>"+error.message;
        return;
    }
    loading.style.display="none";
}

function createCategoryFilter(){
    categoryFilter.innerHTML='<option value="all">All Categories</option>';
    const categories=[...new Set(announcements.map(item=>item.category))];
    categories.forEach(category=>{
        const option=document.createElement("option");
        option.value=category;
        option.textContent=category;
        categoryFilter.appendChild(option);
    });
}

function filterAnnouncements(){
    const search=searchInput.value.toLowerCase().trim();
    const category=categoryFilter.value;
    filteredAnnouncements=announcements.filter(item=>{
        const title=String(item.title).toLowerCase();
        const description=String(item.description).toLowerCase();
        const itemCategory=String(item.category).toLowerCase();
        const searchMatch=title.includes(search)||description.includes(search)||itemCategory.includes(search);
        const categoryMatch=category==="all"||item.category===category;
        return searchMatch&&categoryMatch;
    });
    currentPage=1;
    sortAnnouncements();
    renderAnnouncements();
}

function sortAnnouncements(){
    const value=sortSelect.value;
    if(value==="newest"){
        filteredAnnouncements.sort((a,b)=>new Date(b.date)-new Date(a.date));
    }else if(value==="oldest"){
        filteredAnnouncements.sort((a,b)=>new Date(a.date)-new Date(b.date));
    }else if(value==="titleAZ"){
        filteredAnnouncements.sort((a,b)=>a.title.localeCompare(b.title));
    }else if(value==="titleZA"){
        filteredAnnouncements.sort((a,b)=>b.title.localeCompare(a.title));
    }
}

function renderAnnouncements(){
    container.innerHTML="";
    noResults.style.display="none";
    const start=(currentPage-1)*announcementsPerPage;
    const current=filteredAnnouncements.slice(start,start+announcementsPerPage);
    if(current.length===0){
        noResults.style.display="block";
        pagination.innerHTML="";
        return;
    }
    current.forEach(item=>{
        const row=document.createElement("tr");
        row.innerHTML="<td class='text-center'>"+formatDate(item.date)+"</td><td class='text-center'><span class='badge bg-primary'>"+item.category+"</span></td><td><strong>"+item.title+"</strong></td><td>"+item.description+"</td>";
        container.appendChild(row);
    });
    createPagination();
}

function formatDate(date){
    return new Date(date).toLocaleDateString("en-IN",{day:"2-digit",month:"long",year:"numeric"});
}

function createPagination(){
    pagination.innerHTML="";
    const totalPages=Math.ceil(filteredAnnouncements.length/announcementsPerPage);
    if(totalPages<=1) return;
    const previous=document.createElement("button");
    previous.textContent="Previous";
    previous.className="btn btn-outline-primary me-2";
    previous.disabled=currentPage===1;
    previous.onclick=function(){
        currentPage--;
        renderAnnouncements();
    };
    pagination.appendChild(previous);
    for(let page=1;page<=totalPages;page++){
        const button=document.createElement("button");
        button.textContent=page;
        button.className=page===currentPage?"btn btn-primary me-2":"btn btn-outline-primary me-2";
        button.onclick=function(){
            currentPage=page;
            renderAnnouncements();
        };
        pagination.appendChild(button);
    }
    const next=document.createElement("button");
    next.textContent="Next";
    next.className="btn btn-outline-primary";
    next.disabled=currentPage===totalPages;
    next.onclick=function(){
        currentPage++;
        renderAnnouncements();
    };
    pagination.appendChild(next);
}

searchInput.addEventListener("input",filterAnnouncements);
categoryFilter.addEventListener("change",filterAnnouncements);
sortSelect.addEventListener("change",function(){
    currentPage=1;
    sortAnnouncements();
    renderAnnouncements();
});

loadAnnouncements();