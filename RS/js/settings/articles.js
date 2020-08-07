let all_articles_name_map = {};
let all_articles_map = {};

$(document).ready(function(){

  $("#add_article_close").click(function(){
    $("#add_article_form").hide();
  });
  $("#edit_article_close").click(function(){
    $("#edit_article_form").hide();
  });
  $("#settings_articles_add_rooms").click(function(){
    for(var i=0;i<rooms_list.length;i++)
    {
      var room = rooms_map[rooms_list[i]];
      $.ajax({
          // IZMENITI URL
          url: api_link + 'insert/article',
          method: 'POST',
          data: {
              key: main_key,
              account: account_name,
              lcode: main_lcode,
              id: '',
              category_id: 1,
              barcode: 0,
              code: 0,
              tax_rate: 0,
              description: room.shortname,
              class: 0,
              price: room.price * $("#settings_articles_course").val()
          },
          success: function(rezultat){
            var sve = check_json(rezultat);
            if(sve.status !== "ok") {
              add_change_error(sve.status);
              return;
            }
              var tree = $("#categories").jstree(true);
              tree.deselect_all();
              tree.select_node(1);
              get_all_articles();

          },
          error: function(xhr, textStatus, errorThrown){
            window.alert("Doslo je do greske. " + xhr.responseText);
          }
      });
    }
  });

  $("#add_article").click(function(){
    show_add_article_form();
  });

  $("#settings_articles_id").change(function(){
    let val = $(this).val();
    update_property("articles_id", val);
  });
  $("#settings_articles_course").change(function(){
    let val = $(this).val();
    update_property("currency_course", val);
  });
});


var articles_map = {};

var show_articles = function() {
    show_categories();
    display_articles();
    enable_move();
}
// Proverava da li su podaci za novu kategoriju ispravni
var check_category = function(name) {
    var re = /^([a-zA-Z0-9_ ]+)$/;
    var correct = re.test(name);
    return correct;
};

// Proverava da li su podaci za novi artikal ispravni
var check_article = function() {
    return true;
};

// Unosi novu kategoriju u bazu
var insert_category = function(name, parent_id, id, old_name=null, moving_subtree=false)
{
    // PODACI O KATEGORIJI (ime kategorije, id roditelj kategorije)
    name = name.trim();
    var tree = $("#categories").jstree(true);
    var node = tree.get_node(id);

    if(check_category(name)){
        let url_action;
        if(isNaN(id)){
          url_action = "insert/category";
        }
        else {
          url_action = "edit/category";
        }
        $.ajax({
            // IZMENITI URL
            url: api_link + url_action,
            method: 'POST',
            data:  {
                key: main_key,
                account: account_name,
                lcode: main_lcode,
                id: id,
                name: name,
                parent_id: parent_id
            },
            success: function(rezultat){
              var sve = check_json(rezultat);
              if(sve.status !== "ok") {
                add_change_error(sve.status);
                return;
              }
              // OBRADITI USPEH
              var id_data = sve.data.id;
              // Menja genricki id za id generisan u bazi
              if(id_data['data'] != null) {
                tree.set_id(node, id_data['data']);
              }
            },
            error: function(xhr, textStatus, errorThrown){
                window.alert("Doslo je do greske. " + xhr.responseText);
                window.alert("Doslo je do greske.");
                if (old_name != null) {
                    // Ime cvora se vraca na staru vrednost ako nije uspela izmena
                    tree.set_text(node, old_name);
                } else if (moving_subtree) {
                    // Ako nije uspela promena rodjitelja refresuje stablo sa vazecim podacima u bazi
                    tree.refresh();
                } else {
                    // Brise se cvor iz stabla ako nije uspelo ubacivanje
                    tree.delete_node(node);
                }
            }
        });
    } else {
        if (old_name != null) {
            // Ako nije uspela provera izmenjenog imena vraca se na staru vrednost
            tree.set_text(node, old_name);
        } else if (moving_subtree) {
            // Ako nije uspela promena rodjitelja refresuje stablo sa vazecim podacima u bazi
            tree.refresh();
        }else {
            // Brise se cvor iz stabla ako nije validno ime za novi cvor
            tree.delete_node(node);
        }
    }
};

// Unosi novi artikal u bazu
var insert_article = function(category_id, barcode, code, tax_rate, description, article_class, price, id) {
        $.ajax({
            // IZMENITI URL
            url: api_link + 'insert/article',
            method: 'POST',
            data: {
                key: main_key,
                account: account_name,
                lcode: main_lcode,
                id: id,
                category_id: category_id,
                barcode: barcode,
                code: code,
                tax_rate: tax_rate,
                description: description,
                class: article_class,
                price: price
            },
            success: function(rezultat){
              var sve = check_json(rezultat);
              if(sve.status !== "ok") {
                add_change_error(sve.status);
                return;
              }
              add_change(`Dodat artikal ${description}`, sve.data.id);
              var tree = $("#categories").jstree(true);
              tree.deselect_all();
              tree.select_node(category_id);
              $("#add_article_close").click();
            },
            error: function(xhr, textStatus, errorThrown){
              window.alert("Doslo je do greske. " + xhr.responseText);
            }
        });
};

// Uklanja kategoriju iz baze
var delete_category = function(node) {
    var tree = $("#categories").jstree(true);
    $.ajax({
        // IZMENITI URL
        url: api_link + 'delete/category',
        method: 'POST',
        data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            id: node.id
        },
        success: function(rezultat){
            var sve = check_json(rezultat);
            if(sve.status !== "ok") {
              add_change_error(sve.status);
              return;
            }
            tree.delete_node(node);
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
    });
};

// Uklanja artikal iz baze
var delete_article = function(id) {
    $.ajax({
        // IZMENITI URL
        url: api_link + 'delete/article',
        method: 'POST',
        data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            id: id
        },
        success: function(rezultat){
            var sve = check_json(rezultat);
            if(sve.status !== "ok") {
              add_change_error(sve.status);
              return;
            }
            // OBRADITI USPEH
            add_change(`Obrisan artikal`, -1);
            // Brise se kategorija iz stabla
            var node = $('#categories').jstree('get_selected',true)[0];
            var tree = $("#categories").jstree(true);
            tree.deselect_all();
            tree.select_node(node.id);
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
    });
};

// Iscrtava strukturu artikla

var show_categories = function(){
    let categories = [];
    $.ajax({
        // IZMENITI URL
        url: api_link + 'data/categories',
        method: 'POST',
        data: {
          key: main_key,
          account: account_name,
          lcode: main_lcode,
        },
        success: function(rezultat){
          var sve = check_json(rezultat);
          if(sve.status !== "ok") {
            add_change_error(sve.status);
            return;
          }
            var data = sve.categories;

            // PRILAGODJAVA PODATKE ZA PRIKAZ POMOCU JSTREE
            data.forEach(element => {
                if (element['parent_id'] == 0) {
                    categories.push({
                        id: element['id'],
                        parent: '#',
                        text: element['name']
                    });
                } else {
                    categories.push({
                        id: element['id'],
                        parent: element['parent_id'],
                        text: element['name']
                    });
                }
            });

            // ISCRTAVA STABLO KATEGORIJA
            $('#categories').jstree({
                contextmenu: {
                    items: custom_menu
                },
                core: {
                    check_callback: true,
                    data : categories,
                    themes: {
                        name: 'proton',
                        responsive: true
                    }
                },
                plugins : [ "contextmenu", "search", "dnd" ]
            }).on('ready.jstree', function () {
                setTimeout(function() {
                    $('#categories').jstree(true).select_node(1);
                }, 0);
            });
        },
        error: function(xhr, textStatus, errorThrown){
            window.alert("Doslo je do greske. " + xhr.responseText);
            $('#categories').jstree({
                contextmenu: {
                    items: custom_menu
                },
                core: {
                    check_callback: true,
                    data : {},
                    themes: {
                        name: 'proton',
                        responsive: true
                    }
                },
                plugins : [ "contextmenu", "search", "dnd" ]
            });
        }
    });

}

// KREIRA MENI KATEGORIJA NA DESNI KLIK
function custom_menu($node) {

    var tree = $("#categories").jstree(true);
    var items = {
        'item1' : {
            'label' : 'Dodaj',
            'action' : function () {


                tree.create_node($node, { text: 'Nova kateogorija'}, 'last', function(n){

                    tree.edit(n, n.text, function(n) {
                        insert_category(n.text, n.parent, n.id);
                    });

                });
             }
        },
        'item2' : {
            'label' : 'Izmeni',
            'action' : function () {
                if($($node).attr('parent') != '#'){
                    let old_text = $node.text;
                    tree.edit($node, $node.text , function(n) {
                        insert_category(n.text, n.parent, n.id, old_text);
                    });
                }
             }
        },
        'item3' : {
            'label' : 'Obrisi',
            'action' : function () {
                // Ne sme da obrise root kategoriju
                if($($node).attr('parent') != '#'){
                    // Ne sme da obrise kategoriju koja ima podkategorije
                    if($node.children.length == 0) {
                        delete_category($node);
                        setTimeout(function() {
                            tree.select_node(1);
                        }, 0);
                    }
                }
            }
        }

    }

    return items;
}

// Postavlja callback funkciju kada je selektovana kategorija da prikazuje sve artikle iz kategorije
function display_articles(){
    $("#categories").on(
        "select_node.jstree", function(evt, data){
                // Empty search highlights and expand to selected node
                var tree = $('#categories').jstree(true);
                tree.search('');
                tree._open_to(data.node);

                $.ajax({
                    // IZMENITI URL
                    url: api_link + 'data/articles',
                    method: 'POST',
                    data:{
                        key: main_key,
                        account: account_name,
                        lcode: main_lcode,
                        category_id : data.node.id
                    },
                    success: function(rezultat){
                      var sve = check_json(rezultat);
                      if(sve.status !== "ok") {
                        add_change_error(sve.status);
                        return;
                      }
                        // OBRADITI USPEH
                        $("#articles").empty();
                        // Ispisuju se artikli
                        articles = sve.articles;
                        articles.forEach(function(article){
                          articles_map[article.id] = article;
                          $("#articles").append(`
                            <div class='list_row article'>
                              <div class='article_id'> ${article.id} </div>
                              <div class='article_tax_rate'> ${article.tax_rate} </div>
                              <div class='article_description'> ${article.description} </div>
                              <div class='article_price'> ${article.price} </div>
                              <div class='article_actions'>
                                <div class='list_action' onclick="show_edit_article_form('${article.id}')"><img class='list_action_icon edit' title='Izmeni'> </div>
                                <div class='list_action' onclick="delete_article('${article.id}')"><img class='list_action_icon delete' title='Obriši'> </div>
                              </div>
                            </div>
                            `);
                        });
                        if(articles.length){
                          $("#articles").prepend(`
                            <div class="list_names">
                              <div class='article_id'> ID </div>
                              <div class='article_tax_rate'> Porez </div>
                              <div class='article_description'> Opis </div>
                              <div class='article_price'> Period </div>
                              <div class='article_actions'> Akcije </div>
                            </div>
                            `);
                        }
                        else {
                          $("#articles").append(empty_html("Nema artikala"));
                        }
                    },
                    error: function(xhr, textStatus, errorThrown){
                      window.alert("Doslo je do greske. " + xhr.responseText);
                    }
                });

        }
    );
}

// Enables drag and drop moving categories
function enable_move(){
    $("#categories").on(
        "move_node.jstree", function(evt, data){
            insert_category(data.node.text, data.parent, data.node.id, null, true);
        }
    );
}


function show_edit_article_form(article_id) {
    var article = articles_map[article_id];
    $("#edit_article_form").show();

    document.getElementById('edit_article_id').value = article.id;

    document.getElementById('edit_article_barcode').value = article.barcode;

    document.getElementById('edit_article_code').value = article.code;

    document.getElementById('edit_article_tax_rate').value = article.tax_rate;

    document.getElementById('edit_article_description').value = article.description;

    document.getElementById('edit_article_class').value = article.class;

    document.getElementById('edit_article_price').value = article.price;
}

function edit_article() {

    var id = document.getElementById('edit_article_id').value;
    var barcode = document.getElementById('edit_article_barcode').value;
    var code = document.getElementById('edit_article_code').value;
    var tax_rate = document.getElementById('edit_article_tax_rate').value;
    var description = document.getElementById('edit_article_description').value;
    var article_class = document.getElementById('edit_article_class').value;
    var price = document.getElementById('edit_article_price').value;
    var node = $('#categories').jstree('get_selected',true)[0];
    $.ajax({
        // IZMENITI URL
        url: api_link + 'edit/article',
        method: 'POST',
        data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            id: id,
            category_id: node.id,
            barcode: barcode,
            code: code,
            tax_rate: tax_rate,
            description: description,
            class: article_class,
            price: price
        },
        success: function(rezultat){
          var sve = check_json(rezultat);
          if(sve.status !== "ok") {
            add_change_error(sve.status);
            return;
          }
          add_change(`Izmenjen artikal ${description}`, sve.data.id);
          var tree = $("#categories").jstree(true);
          tree.deselect_all();
          tree.select_node(node.id);
          $("#edit_article_close").click();
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
    });
}

function add_article() {
    var barcode = document.getElementById('add_article_barcode').value;
    var code = document.getElementById('add_article_code').value;
    var tax_rate = document.getElementById('add_article_tax_rate').value;
    var description = document.getElementById('add_article_description').value;
    var article_class = document.getElementById('add_article_class').value;
    var price = document.getElementById('add_article_price').value;
    var node = $('#categories').jstree('get_selected',true)[0];
    insert_article(node.id, barcode, code, tax_rate, description, article_class, price, "");
    add_article_form.style.display = "none";
}

// Uzima forme
var add_article_form = document.getElementById("add_article_form");
var edit_article_form = document.getElementById("edit_article_form");

// Uzima dugmice
var add_btn = document.getElementById("add_article");

function show_add_article_form() {
  $("#add_article_form").show();
  document.getElementById('add_article_barcode').value = '';
  document.getElementById('add_article_code').value = '';
  document.getElementById('add_article_tax_rate').value = '';
  document.getElementById('add_article_description').value = '';
  document.getElementById('add_article_class').value = '';
  document.getElementById('add_article_price').value = '';
}


function sleep(milliseconds) {
	return new Promise(resolve => setTimeout(resolve, milliseconds))
}
