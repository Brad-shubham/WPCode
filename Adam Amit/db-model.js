import { s, setDb, db, dbConfig, drawings, activeDrawing } from "./state";

window.addEventListener("load", () => {
  createDb(); // Creating db at the starting
  retreive(); // Retreiving data at start
  // *************** CREATING DB ******************************* //
  function createDb() {
    const indexedDB = window.indexedDB || window.mozIndexedDB;
    const openReq = indexedDB.open(dbConfig.dbName, dbConfig.dbVersion);
    openReq.onupgradeneeded = (e) => {
      setDb(e.target.result);
      // Creating object store:
      if (db) {
        db.createObjectStore(dbConfig.storeName, { keyPath: "id" });
      }
    };
    openReq.onsuccess = (e) => {
      if (!db) { 
        setDb(e.target.result);
      }
    };
    openReq.onerror = (e) => {
      console.error(e);
    };
  }
  // ********************************************************** //

  function save(imgUrl) {
    const id = JSON.parse(localStorage.getItem("currentItem"))?.id;
    const newDrawing = {
      id: id || (Math.random() * 10).toString(),
      imgUrl: imgUrl,
      data: activeDrawing || [],
    };
    const transaction = db.transaction([dbConfig.storeName], "readwrite");
    const store = transaction.objectStore(dbConfig.storeName);
    const req = store.put(newDrawing);
    req.onsuccess = (e) => {
      const toast = s("#toast");
      toast.style.display = "block";
      window.location.href = window.location.origin.toString();
      setTimeout(() => {
        toast.style.display = "none";
      }, 800);
    };
  }

  function retreive() {
    setTimeout(() => {
      const transaction = db.transaction([dbConfig.storeName], "readonly");
      const store = transaction.objectStore(dbConfig.storeName);
      const req = store.openCursor();
      req.onsuccess = (e) => {
        const cursor = e.target.result;
        if (cursor) {
          drawings.push({ ...cursor.value });
          cursor.continue();
        } else {
          buildCards();
        }
      };
    }, 100);
  }

  function buildCards() {
    const content = s("#content");
    const div = document.createElement("div");
    let childToAppend = "";
    if (drawings && drawings.length) {
      drawings.forEach((data, ind) => {
        childToAppend += `
            <figure class="painting" id="${"db" + ind}">
            <span class="del-btn" id="${"del" + ind}" data-id="${
          data.id
        }">&#10006</span>
            <div class="image"><img src="${data.imgUrl}"
                alt="pr-sample23" /></div>
            <figcaption>
              <h3>Drawing: ${ind + 1}</h3>
            </figcaption>
          </figure>
            `;
      });
      div.innerHTML = childToAppend;
      content.append(div);
    } else {
      childToAppend += `<div class="no-data">No Data Available</div>`;
      div.innerHTML = childToAppend;
    }
    drawings.forEach((data, ind) => {
      const el = document.querySelector(`#db${ind}`);
      el.addEventListener("click", (e) =>
        currentDrawing({ id: data.id, ind: ind })
      );
      const delBtn = document.querySelector(`#del${ind}`);
      delBtn.addEventListener("click", (e) => {
        const id = e.target.dataset.id;
        deleteItem(id);
        e.stopImmediatePropagation();
      });
    });
  }

  function currentDrawing(data) {
    localStorage.setItem("currentItem", JSON.stringify(data));
    window.location.href = window.location.href.toString() + `drawing/`;
  }

  function deleteItem(id) {
    if (id) {
      const transaction = db.transaction(["drawings"], "readwrite");
      const objectStore = transaction.objectStore("drawings");
      const req = objectStore.delete(id);
      transaction.oncomplete = (e) => {
        window.location.reload();
      };
    }
  }

  //* ********************************************************* */

  s("#save_btn")?.addEventListener("click", (e) => {
    const canvas = s("#canvas");
    const imgUrl = canvas.toDataURL("image/png");
    save(imgUrl);
  });

  // Binding export feature to export button::
  s("#export_btn")?.addEventListener("click", (e) => {
    var canvas = s("#canvas");
    var anchor = document.createElement("a");
    anchor.href = canvas.toDataURL("image/png");
    anchor.download = "IMAGE.PNG";
    anchor.click();
  });
});
