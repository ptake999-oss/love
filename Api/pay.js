export default async function handler(req, res) {

  if (req.method !== "POST") {
    return res.status(405).json({ error: "Method not allowed" });
  }

  // -----------------------------
  // BASE URL
  // -----------------------------
  const BASE_URL = "https://rvcreationstore.in";

  // -----------------------------
  // CASHFREE API
  // -----------------------------
  const API_URL = "https://api.cashfree.com/pg/orders";

  // -----------------------------
  // GET AMOUNT FROM FRONTEND
  // -----------------------------
  const { amount } = req.body;

  const orderAmount = amount ? parseFloat(amount).toFixed(2) : "199.00";

  // -----------------------------
  // ORDER ID GENERATOR
  // -----------------------------
  function generateOrderId() {
    return "ORD_" + Math.random().toString(36).substring(2, 15).toUpperCase();
  }

  const orderId = generateOrderId();

  // -----------------------------
  // RANDOM CUSTOMER (same logic as PHP)
  // -----------------------------
  const firstNames = ["Amit","Priya","Rahul","Sneha","Karan","Meera","Ravi","Anita"];
  const lastNames = ["Sharma","Patel","Singh","Khan","Mehta","Gupta","Iyer","Das"];

  const randomFirst = firstNames[Math.floor(Math.random()*firstNames.length)];
  const randomLast = lastNames[Math.floor(Math.random()*lastNames.length)];

  const customerName = randomFirst + " " + randomLast;

  const emailName = customerName.replace(" ","").toLowerCase();

  const customerEmail = emailName + Math.floor(Math.random()*99) + "@gmail.com";

  const customerPhone = "9" + Math.floor(100000000 + Math.random()*900000000);

  // -----------------------------
  // CREATE CASHFREE PAYLOAD
  // -----------------------------
  const payload = {
    order_id: orderId,
    order_amount: orderAmount,
    order_currency: "INR",

    customer_details: {
      customer_id: "CUST" + Math.floor(Math.random()*9999),
      customer_name: customerName,
      customer_email: customerEmail,
      customer_phone: customerPhone
    },

    order_meta: {
      return_url: `${BASE_URL}/success.html?order_id=${orderId}`,
      notify_url: `${BASE_URL}/callback`,
      payment_methods: "cc,dc,upi"
    }
  };

  try {

    const response = await fetch(API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "x-api-version": "2023-08-01",
        "X-Client-Id": process.env.CASHFREE_CLIENT_ID,
        "X-Client-Secret": process.env.CASHFREE_SECRET_KEY
      },
      body: JSON.stringify(payload)
    });

    const data = await response.json();

    // -----------------------------
    // RETURN SESSION ID
    // -----------------------------
    if (data.payment_session_id) {

      return res.status(200).json({
        paymentSessionId: data.payment_session_id,
        orderId: orderId
      });

    } else {

      return res.status(400).json({
        error: "Cashfree error",
        details: data
      });

    }

  } catch (error) {

    return res.status(500).json({
      error: "Payment API error",
      details: error.message
    });

  }
}