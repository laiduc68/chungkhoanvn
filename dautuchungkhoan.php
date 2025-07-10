<?php
/*
Plugin Name: Quản lý Đầu tư Chứng khoán Cá nhân
Description: Ghi lại, theo dõi danh mục cổ phiếu, dòng tiền và lãi/lỗ cá nhân. Giao diện tiếng Việt, chỉ Admin sử dụng.
Version: 1.0
Author:laivanduc.vn
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page(
        'Quản lý Chứng khoán',
        'Chứng khoán',
        'manage_options',
        'stock-portfolio',
        'stock_portfolio_main_page',
        'dashicons-chart-line'
    );
});

function stock_portfolio_main_page() {
    ?>
    <div class="wrap">
        <h1 style="color:#2263b6;letter-spacing:1px;">Quản lý Đầu tư Chứng khoán <span style="font-size:18px;color:#f5ac31;">📈</span></h1>
        <p><strong>Ghi lại, theo dõi toàn bộ giao dịch cổ phiếu, dòng tiền và lãi/lỗ cá nhân. (Dữ liệu lưu trình duyệt cá nhân.)</strong></p>
        <div id="stock-portfolio-root"></div>
    </div>
    <style>
#stock-portfolio-root input, #stock-portfolio-root select {
  min-width: 120px; margin: 2px 0; padding: 4px 9px; border-radius:7px; border:1px solid #b7c3da; background:#f7fafd;
}
#stock-portfolio-root table {
  border-collapse:separate; border-spacing:0 5px; width:100%; background:transparent;
}
#stock-portfolio-root th, #stock-portfolio-root td {
  padding: 8px 12px; border:none; background: #fff;
}
#stock-portfolio-root th {
  background: #e6eef7; color:#244971; font-weight:600; font-size:15px;
  border-radius:8px 8px 0 0; border-bottom: 1.5px solid #c5d5e7;
}
#stock-portfolio-root tr {
  box-shadow: 0 1px 6px #dae7f3cc; border-radius:8px;
}
#stock-portfolio-root tr:hover td { background: #f0f4fa !important; }
#stock-portfolio-root tr[style*="background:#fff;"]:nth-child(even) td {
  background:#f8fafc !important;
}
#stock-portfolio-root td, #stock-portfolio-root th { text-align: center; font-size: 15px; }
#stock-portfolio-root td.money-in { color:#219652; font-weight:600; }
#stock-portfolio-root td.money-out { color:#db3545; font-weight:600; }
#stock-portfolio-root td.buy { color:#2471ba; }
#stock-portfolio-root td.sell { color:#d88c19; }
#stock-portfolio-root button {
  padding: 3px 13px; border-radius:8px; border: none;
  background:#e8f0fa; color:#244971; font-weight:600;
  cursor:pointer; box-shadow:0 2px 8px #e1e8f1aa;
  margin:1px 0; transition: background 0.17s;
}
#stock-portfolio-root button:active { background:#f3f9ff; }
#stock-portfolio-root .success { color: #23ad3e; }
#stock-portfolio-root .error { color: #d52137; }
#stock-portfolio-root .modal-bg {
  position:fixed;z-index:10000;left:0;top:0;width:100vw;height:100vh;background:rgba(90,110,140,0.22);display:flex;align-items:center;justify-content:center;
}
#stock-portfolio-root .modal-popup {
  background:#fff; padding:27px 28px 19px 28px; border-radius:13px; box-shadow:0 6px 60px #3450751f; min-width:330px; max-width:95vw;
  display:flex; flex-direction:column; gap:12px; align-items:stretch;
  animation:fadeInUp .23s cubic-bezier(.36,1.7,.61,.82);
}
@keyframes fadeInUp { 0%{transform:translateY(30px) scale(.94);opacity:0;} 100%{transform:translateY(0) scale(1);opacity:1;} }
#stock-portfolio-root .modal-popup h3 { font-size:19px;margin:0 0 12px 0;text-align:center; }
#stock-portfolio-root .modal-popup .modal-row { margin-bottom:8px; }
#stock-portfolio-root .modal-popup label { display:block;font-size:14px;color:#496190;margin-bottom:3px;text-align:left; }
#stock-portfolio-root .modal-popup input, #stock-portfolio-root .modal-popup select {
  width:100%;margin-bottom:6px;
}
#stock-portfolio-root .modal-popup .modal-btns { display:flex;justify-content:center;gap:13px;margin-top:7px; }
#stock-portfolio-root .modal-popup .modal-btns button { min-width:88px; }
#stock-portfolio-root .modal-popup .danger { background:#ffdede;color:#bd2323; }
.big-section-title {
  font-size: 1.45rem;
  color: #1356a6;
  font-weight: 700;
  margin: 27px 0 11px 0;
  letter-spacing:0.2px;
  display: flex;
  align-items: center;
  gap: 7px;
}
.big-section-title .emoji {
  font-size:1.35em;
}
@media (max-width: 600px) {
  #stock-portfolio-root table, #stock-portfolio-root th, #stock-portfolio-root td { font-size:13px; }
  #stock-portfolio-root .modal-popup { padding:13px 2vw 14px 2vw;}
  .big-section-title { font-size:1.1rem; }
}
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        function formatDateDMY(dateStr) {
            if (!dateStr) return '';
            let d = new Date(dateStr);
            if (isNaN(d)) return dateStr;
            let day = String(d.getDate()).padStart(2, '0');
            let month = String(d.getMonth()+1).padStart(2, '0');
            let year = d.getFullYear();
            return `${day}/${month}/${year}`;
        }

        function todayStr() {
            let d = new Date();
            let day = String(d.getDate()).padStart(2, '0');
            let month = String(d.getMonth()+1).padStart(2, '0');
            let year = d.getFullYear();
            return `${year}-${month}-${day}`;
        }

        const root = document.getElementById('stock-portfolio-root');
        let data = JSON.parse(localStorage.getItem('stock_portfolio_data') || '{"transactions":[],"cash":0,"notes":""}');
        let msg = '';
        let editIndex = -1;
        let editObj = null;
        let pageBuy = 1, pageSell = 1;
        window.pageCash = 1;
        const PER_PAGE = 30;
        const PER_PAGE_CASH = 30;

        function save() {
            localStorage.setItem('stock_portfolio_data', JSON.stringify(data));
            render();
        }

        function addTransaction(e) {
            e.preventDefault();
            const form = e.target;
            let type = form.type.value;
            let symbol = form.symbol.value.trim().toUpperCase();
            let qty = Number(form.qty.value);
            let price = Number(form.price.value);
            let fee = Number(form.fee.value);
            let date = form.date.value;
            let note = form.note.value;
            if(!symbol || qty <= 0 || price <= 0 || !date) {
                msg = '<span class="error">Vui lòng nhập đủ thông tin hợp lệ.</span>'; render(); return;
            }
            if (editIndex >= 0) {
                data.transactions[editIndex] = {type, symbol, qty, price, fee, date, note};
                msg = '<span class="success">Đã cập nhật giao dịch.</span>';
                editIndex = -1; editObj = null;
            } else {
                data.transactions.push({type, symbol, qty, price, fee, date, note});
                msg = '<span class="success">Đã thêm giao dịch.</span>';
            }
            save();
            form.reset();
            form.date.value = todayStr();
        }

        function addCash(e) {
            e.preventDefault();
            let amount = Number(e.target.amount.value);
            let note = e.target.note.value;
            let type = e.target.type.value;
            if(type === "deposit") data.cash += amount;
            else data.cash -= amount;
            data.transactions.push({type: type==='deposit'?'Nạp tiền':'Rút tiền', symbol: '-', qty: 0, price: amount, fee: 0, date: todayStr(), note});
            msg = '<span class="success">' + (type==='deposit'?'Đã nạp tiền':'Đã rút tiền') + '.</span>';
            save();
            e.target.reset();
        }

        // Popup Sửa
        function showEditPopup(idx) {
            editIndex = idx;
            editObj = {...data.transactions[idx]};
            render();
        }
        function closeEditPopup() {
            editIndex = -1; editObj = null;
            render();
        }
        function handleEditInput(e) {
            const f = e.target;
            editObj.type = f.type.value;
            editObj.symbol = f.symbol.value.trim().toUpperCase();
            editObj.qty = Number(f.qty.value);
            editObj.price = Number(f.price.value);
            editObj.fee = Number(f.fee.value);
            editObj.date = f.date.value;
            editObj.note = f.note.value;
        }
        function saveEditPopup(e) {
            e.preventDefault();
            if(!editObj.symbol || editObj.qty <= 0 || editObj.price <= 0 || !editObj.date) {
                alert('Vui lòng nhập đủ thông tin hợp lệ.'); return;
            }
            data.transactions[editIndex] = {...editObj};
            msg = '<span class="success">Đã cập nhật giao dịch.</span>';
            editIndex = -1; editObj = null;
            save();
        }

        // Popup xác nhận xoá
        let delIndex = -1;
        function showDeletePopup(idx) { delIndex = idx; render(); }
        function closeDeletePopup() { delIndex = -1; render(); }
        function doDelete() {
            data.transactions.splice(delIndex, 1);
            delIndex = -1;
            msg = '<span class="success">Đã xóa giao dịch.</span>';
            save();
        }

        function calcReport() {
            let portfolio = {};
            let profit = 0;
            let cash = data.cash;
            data.transactions.forEach(tx => {
                if(tx.type !== "Mua" && tx.type !== "Bán") return;
                let sym = tx.symbol;
                if(!portfolio[sym]) portfolio[sym] = {qty:0, cost:0};
                if(tx.type === "Mua") {
                    portfolio[sym].cost += tx.qty * tx.price + (tx.fee||0);
                    portfolio[sym].qty += tx.qty;
                    cash -= tx.qty * tx.price + (tx.fee||0);
                }
                else if(tx.type === "Bán" && portfolio[sym].qty > 0) {
                    let avg_cost = portfolio[sym].cost / portfolio[sym].qty;
                    let cost_out = avg_cost * tx.qty;
                    let money_in = tx.qty * tx.price - (tx.fee||0);
                    profit += money_in - cost_out;
                    portfolio[sym].qty -= tx.qty;
                    portfolio[sym].cost -= cost_out;
                    cash += money_in;
                }
            });
            let livePortfolio = {};
            for (let sym in portfolio) {
                if (portfolio[sym].qty > 0)
                    livePortfolio[sym] = portfolio[sym];
            }
            return {portfolio: livePortfolio, profit, cash};
        }

        function paginate(arr, page, perpage) {
            const total = arr.length;
            const maxPage = Math.ceil(total / perpage);
            const start = (page - 1) * perpage;
            const end = start + perpage;
            return {
                data: arr.slice(start, end),
                maxPage
            };
        }

        function render() {
            const {portfolio, profit, cash} = calcReport();

            const buys = data.transactions
                .map((tx, idx) => ({...tx, idx}))
                .filter(tx => tx.type === "Mua")
                .sort((a, b) => new Date(b.date) - new Date(a.date));
            const sells = data.transactions
                .map((tx, idx) => ({...tx, idx}))
                .filter(tx => tx.type === "Bán")
                .sort((a, b) => new Date(b.date) - new Date(a.date));

            const pagBuy = paginate(buys, pageBuy, PER_PAGE);
            const pagSell = paginate(sells, pageSell, PER_PAGE);

            const cashTxs = data.transactions
              .map((tx, idx) => ({...tx, idx}))
              .filter(tx => tx.type==="Nạp tiền"||tx.type==="Rút tiền")
              .sort((a,b)=>new Date(b.date)-new Date(a.date));
            const pageCash = window.pageCash || 1;
            const pagCash = paginate(cashTxs, pageCash, PER_PAGE_CASH);

            // Lấy danh sách mã cổ phiếu đang nắm giữ
            const holdingList = Object.keys(portfolio);

            // Form nhập liệu lên đầu
            let html = `
<div style="max-width:900px;margin:0 auto 18px auto;padding:0;">
  <form onsubmit="return false;" id="form-cash" style="margin-bottom:18px;">
      <h3 style="color:#184182;">Nạp / Rút tiền</h3>
      <select name="type">
          <option value="deposit">Nạp tiền</option>
          <option value="withdraw">Rút tiền</option>
      </select>
      <input type="number" name="amount" placeholder="Số tiền" step="any" required/>
      <input type="text" name="note" placeholder="Ghi chú"/>
      <button>Nạp/Rút</button>
  </form>
  <form onsubmit="return false;" id="form-transaction" style="margin-bottom:25px;">
      <h3 style="color:#184182;">Giao dịch Cổ phiếu</h3>
      <select name="type" id="typeSelect">
          <option value="Mua">Mua</option>
          <option value="Bán">Bán</option>
      </select>
      <span id="symbolInputWrap" style="display:inline-block;">
        <input type="text" name="symbol" id="symbolInput" placeholder="Mã CP (VD: FPT)" required style="width:100px;" />
      </span>
      <input type="number" name="qty" placeholder="Số lượng" required style="width:90px;" />
      <input type="number" name="price" placeholder="Giá/CP" step="any" required style="width:100px;" />
      <input type="number" name="fee" placeholder="Phí" step="any" value="0" style="width:70px;" />
      <input type="date" name="date" required style="width:145px;" />
      <input type="text" name="note" placeholder="Lý do/Ghi chú" style="width:130px;" />
      <button>${editIndex >= 0 ? "Lưu sửa" : "Thêm giao dịch"}</button>
      ${editIndex >= 0 ? '<button type="button" id="btn-cancel-edit">Huỷ sửa</button>' : ""}
  </form>
  <div style="background:linear-gradient(92deg,#f3f6fa 40%,#eaf2f7 100%);box-shadow:0 4px 20px #0012; border-radius:18px; padding:22px 16px 18px 16px; margin:0 0 20px 0; display:flex; flex-direction:column;align-items:center;">
    <div style="width:100%;display:flex;flex-wrap:wrap;justify-content:center;gap:24px;">
      <div style="min-width:200px; background:#fff; border-radius:13px; box-shadow:0 2px 8px #e3ebf4; padding:15px 19px; margin-bottom:12px;">
        <div style="color:#6b7a93;font-size:15px;margin-bottom:7px;">Tổng tiền mặt</div>
        <div style="font-size:2.1rem; font-weight:bold; color:#184182;text-align:right;">
          ${cash.toLocaleString()} <span style="font-size:1.1rem;color:#aaa;">VND</span>
        </div>
      </div>
      <div style="min-width:200px; background:#fff; border-radius:13px; box-shadow:0 2px 8px #e3ebf4; padding:15px 19px; margin-bottom:12px;">
        <div style="color:#6b7a93;font-size:15px;margin-bottom:7px;">Lãi/Lỗ đã thực hiện</div>
        <div style="font-size:2.1rem;font-weight:bold;text-align:right;display:flex;align-items:center;justify-content:flex-end;color:${profit>0?'#20974b':(profit<0?'#e9453a':'#454d62')}">
          ${profit>0?'😊':(profit<0?'😅':'😐')}
          <span style="margin-left:9px;">${profit.toLocaleString()} <span style="font-size:1.1rem;color:#aaa;">VND</span></span>
        </div>
      </div>
    </div>
    <div style="width:100%;margin-top:18px;">
      <h3 style="padding:0 0 8px 0;margin:0;font-size:20px;color:#184182;">Danh mục hiện tại</h3>
      <div style="overflow-x:auto;">
        <table style="min-width:440px;width:100%;">
          <thead>
            <tr>
              <th style="padding:7px 12px;border-radius:8px 0 0 8px;">Mã CP</th>
              <th style="padding:7px 12px;">Số lượng</th>
              <th style="padding:7px 12px;">Giá vốn TB</th>
              <th style="padding:7px 12px;border-radius:0 8px 8px 0;">Tổng vốn</th>
            </tr>
          </thead>
          <tbody>
            ${
              Object.entries(portfolio).length === 0
              ? `<tr><td colspan="4" style="padding:16px 0;text-align:center; color:#bbb; font-size:16px; background:#fff;border-radius:0 0 8px 8px;">Không có cổ phiếu nào</td></tr>`
              : Object.entries(portfolio).map(([symbol, info], i, arr) => `
                <tr style="background:#fff;">
                  <td style="text-align:center; font-weight:600; padding:7px 12px; border-radius:${i===arr.length-1?'0 0 0 8px':''};">${symbol}</td>
                  <td style="text-align:right; padding:7px 12px;">${info.qty}</td>
                  <td style="text-align:right; padding:7px 12px;">${(info.qty > 0 ? (info.cost/info.qty).toLocaleString(undefined, {maximumFractionDigits:3}) : "-")}</td>
                  <td style="text-align:right; padding:7px 12px; border-radius:${i===arr.length-1?'0 0 8px 0':''};">${info.cost.toLocaleString(undefined, {maximumFractionDigits:0})}</td>
                </tr>
              `).join('')
            }
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div>${msg}</div>

<!-- BẢNG LỊCH SỬ NẠP RÚT & GIAO DỊCH -->
<h3 class="big-section-title"><span class="emoji">💸</span> Lịch sử Nạp / Rút tiền</h3>
<div style="overflow-x:auto;">
<table style="min-width:340px;">
  <thead>
    <tr>
      <th>#</th>
      <th>Loại</th>
      <th>Số tiền</th>
      <th>Ngày</th>
      <th>Ghi chú</th>
    </tr>
  </thead>
  <tbody>
    ${
      pagCash.data.length ? pagCash.data.map((tx, i) => `
        <tr style="background:#fff;">
          <td>${(pageCash-1)*PER_PAGE_CASH+i+1}</td>
          <td class="${tx.type==='Nạp tiền'?'money-in':'money-out'}">${tx.type}</td>
          <td style="text-align:right;">${tx.price.toLocaleString()}</td>
          <td>${formatDateDMY(tx.date)}</td>
          <td>${tx.note||''}</td>
        </tr>
      `).join('') : `<tr><td colspan="5" style="text-align:center;color:#888;">Chưa có giao dịch nào</td></tr>`
    }
    ${pagCash.maxPage > 1 ? `
      <tr><td colspan="5" style="text-align:center;">
        <button ${pageCash==1?'disabled':''} onclick="gotoCashPage(${pageCash-1})">Trước</button>
        Trang ${pageCash}/${pagCash.maxPage}
        <button ${pageCash==pagCash.maxPage?'disabled':''} onclick="gotoCashPage(${pageCash+1})">Sau</button>
      </td></tr>
    ` : ""}
  </tbody>
</table>
</div>

<h3 class="big-section-title"><span class="emoji">🛒</span> Giao dịch Mua</h3>
<table>
    <tr>
        <th>#</th><th>Mã CP</th><th>Số lượng</th><th>Giá</th><th>Phí</th><th>Ngày</th><th>Ghi chú</th><th>Sửa</th><th>Xóa</th>
    </tr>
    ${pagBuy.data.map((tx, i) => `
        <tr>
            <td>${(pageBuy-1)*PER_PAGE+i+1}</td>
            <td class="buy">${tx.symbol}</td>
            <td>${tx.qty}</td>
            <td>${tx.price}</td>
            <td>${tx.fee||''}</td>
            <td>${formatDateDMY(tx.date)}</td>
            <td>${tx.note||''}</td>
            <td><button onclick="showEditPopup(${tx.idx})">Sửa</button></td>
            <td><button onclick="showDeletePopup(${tx.idx})">Xóa</button></td>
        </tr>
    `).join('')}
</table>
${pagBuy.maxPage > 1 ? `
    <div>
        <button ${pageBuy==1?'disabled':''} onclick="gotoBuyPage(${pageBuy-1})">Trước</button>
        Trang ${pageBuy}/${pagBuy.maxPage}
        <button ${pageBuy==pagBuy.maxPage?'disabled':''} onclick="gotoBuyPage(${pageBuy+1})">Sau</button>
    </div>
` : ""}

<h3 class="big-section-title"><span class="emoji">💼</span> Giao dịch Bán</h3>
<table>
    <tr>
        <th>#</th><th>Mã CP</th><th>Số lượng</th><th>Giá</th><th>Phí</th><th>Ngày</th><th>Ghi chú</th><th>Sửa</th><th>Xóa</th>
    </tr>
    ${pagSell.data.map((tx, i) => `
        <tr>
            <td>${(pageSell-1)*PER_PAGE+i+1}</td>
            <td class="sell">${tx.symbol}</td>
            <td>${tx.qty}</td>
            <td>${tx.price}</td>
            <td>${tx.fee||''}</td>
            <td>${formatDateDMY(tx.date)}</td>
            <td>${tx.note||''}</td>
            <td><button onclick="showEditPopup(${tx.idx})">Sửa</button></td>
            <td><button onclick="showDeletePopup(${tx.idx})">Xóa</button></td>
        </tr>
    `).join('')}
</table>
${pagSell.maxPage > 1 ? `
    <div>
        <button ${pageSell==1?'disabled':''} onclick="gotoSellPage(${pageSell-1})">Trước</button>
        Trang ${pageSell}/${pagSell.maxPage}
        <button ${pageSell==pagSell.maxPage?'disabled':''} onclick="gotoSellPage(${pageSell+1})">Sau</button>
    </div>
` : ""}

<!-- Popup Sửa giao dịch -->
${editIndex>=0 && editObj ? `
  <div class="modal-bg">
    <form class="modal-popup" id="modal-edit-form">
      <h3>Sửa Giao dịch</h3>
      <div class="modal-row">
        <label>Loại</label>
        <select name="type" id="modalTypeSelect">
          <option value="Mua" ${editObj.type=="Mua"?"selected":""}>Mua</option>
          <option value="Bán" ${editObj.type=="Bán"?"selected":""}>Bán</option>
        </select>
      </div>
      <div class="modal-row" id="modalSymbolInputWrap">
      </div>
      <div class="modal-row">
        <label>Số lượng</label>
        <input type="number" name="qty" value="${editObj.qty||""}" required />
      </div>
      <div class="modal-row">
        <label>Giá/CP</label>
        <input type="number" name="price" value="${editObj.price||""}" step="any" required />
      </div>
      <div class="modal-row">
        <label>Phí</label>
        <input type="number" name="fee" value="${editObj.fee||0}" step="any" />
      </div>
      <div class="modal-row">
        <label>Ngày</label>
        <input type="date" name="date" value="${editObj.date||todayStr()}" required />
      </div>
      <div class="modal-row">
        <label>Lý do/Ghi chú</label>
        <input name="note" value="${editObj.note||""}" />
      </div>
      <div class="modal-btns">
        <button type="submit">Lưu sửa</button>
        <button type="button" onclick="closeEditPopup()" class="danger">Huỷ</button>
      </div>
    </form>
  </div>
`:""}

<!-- Popup xác nhận xoá -->
${delIndex>=0 ? `
  <div class="modal-bg">
    <div class="modal-popup" style="align-items:center;">
      <h3 style="color:#e36a6a;font-size:18px;">Xác nhận XÓA?</h3>
      <div style="color:#28425f;font-size:15px;padding:6px 0 13px 0;">Bạn chắc chắn muốn xóa giao dịch này không?</div>
      <div class="modal-btns">
        <button onclick="doDelete()" class="danger">Xóa</button>
        <button onclick="closeDeletePopup()">Huỷ</button>
      </div>
    </div>
  </div>
`:""}
`;

            root.innerHTML = html;

            // Sự kiện form nhập cổ phiếu: chuyển input Mã CP thành select khi là Bán
            let formTx = document.getElementById('form-transaction');
            let typeSel = document.getElementById('typeSelect');
            let symbolWrap = document.getElementById('symbolInputWrap');
            let symbolInput = document.getElementById('symbolInput');
            function updateSymbolInput() {
                if (typeSel.value === 'Bán') {
                    // Danh sách cổ phiếu đang nắm giữ
                    if (holdingList.length == 0) {
                        symbolWrap.innerHTML = '<select name="symbol" id="symbolInput" required disabled><option value="">--Không có cổ phiếu nào--</option></select>';
                    } else {
                        symbolWrap.innerHTML = '<select name="symbol" id="symbolInput" required>' + holdingList.map(m => `<option value="${m}">${m}</option>`).join('') + '</select>';
                    }
                } else {
                    symbolWrap.innerHTML = '<input type="text" name="symbol" id="symbolInput" placeholder="Mã CP (VD: FPT)" required style="width:100px;" />';
                }
            }
            typeSel.onchange = updateSymbolInput;
            updateSymbolInput();

            // Tự động set ngày hôm nay khi load
            formTx.date.value = todayStr();

            document.getElementById('form-transaction').onsubmit = addTransaction;
            document.getElementById('form-cash').onsubmit = addCash;

            window.showEditPopup = showEditPopup;
            window.closeEditPopup = closeEditPopup;
            window.showDeletePopup = showDeletePopup;
            window.closeDeletePopup = closeDeletePopup;
            window.doDelete = doDelete;
            window.gotoBuyPage = function(p) { pageBuy = p; render(); }
            window.gotoSellPage = function(p) { pageSell = p; render(); }
            window.gotoCashPage = function(p) { window.pageCash = p; render(); }

            // Popup Sửa giao dịch: chuyển input Mã CP thành select nếu là Bán
            if (editIndex>=0 && editObj) {
                let f = document.getElementById('modal-edit-form');
                // render symbol input
                function updateModalSymbol() {
                    let wrap = document.getElementById('modalSymbolInputWrap');
                    if (f.type.value==='Bán') {
                        if (holdingList.length == 0) {
                            wrap.innerHTML = `<label>Mã CP</label><select name="symbol" disabled><option value="">--Không có cổ phiếu nào--</option></select>`;
                        } else {
                            wrap.innerHTML = `<label>Mã CP</label><select name="symbol" required>` + holdingList.map(m => `<option value="${m}"${editObj.symbol==m?' selected':''}>${m}</option>`).join('') + `</select>`;
                        }
                    } else {
                        wrap.innerHTML = `<label>Mã CP</label><input name="symbol" value="${editObj.symbol||""}" required />`;
                    }
                }
                updateModalSymbol();
                f.onsubmit = saveEditPopup;
                f.type.onchange = function(){updateModalSymbol(); handleEditInput({target:f})};
                f.symbol && (f.symbol.oninput = function(){ handleEditInput({target:f}) });
                f.qty.oninput = f.price.oninput = f.fee.oninput = f.date.oninput = f.note.oninput = function(){ handleEditInput({target:f}) };
            }
        }
        render();
    });
    </script>
    <?php
}
?>
